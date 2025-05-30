<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    /**
     * Get all agents.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Only admin, staff, and agents can view agents
            if ($user->role === 'client') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clients cannot access agent data',
                ], 403);
            }
            
            $query = Agent::with('user');
            
            // Apply filters if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Handle search by name
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Map front-end sort fields to actual database fields if needed
            $fieldMap = [
                'name' => 'users.name',
                'email' => 'users.email',
                'status' => 'agents.status',
                'created_at' => 'agents.created_at',
            ];
            
            $dbSortField = $fieldMap[$sortField] ?? 'agents.created_at';
            
            if (in_array($sortField, ['name', 'email'])) {
                $query->join('users', 'agents.user_id', '=', 'users.id')
                      ->select('agents.*')
                      ->orderBy($dbSortField, $sortDirection);
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
            
            $agents = $query->paginate($request->input('per_page', 15));
            
            // If user is an agent, only show their own data
            if ($user->role === 'agent' && $user->agent) {
                $agents = Agent::with('user')
                    ->where('id', $user->agent->id)
                    ->paginate(1);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $agents,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve agents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific agent.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $agent = Agent::with([
                'user', 
                'orders.carpets', 
                'orders.client.user', 
                'commissions.invoice'
            ])->findOrFail($id);
            
            // Check permissions
            if ($user->role === 'client') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clients cannot access agent data',
                ], 403);
            }
            
            if ($user->role === 'agent' && $user->agent && $user->agent->id !== $agent->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only view your own agent data',
                ], 403);
            }
            
            // Get commission statistics
            $totalPendingCommission = $agent->commissions()
                ->where('status', 'pending')
                ->sum('total_commission');
                
            $totalPaidCommission = $agent->commissions()
                ->where('status', 'paid')
                ->sum('total_commission');
                
            // Get order statistics
            $orderStats = $agent->orders()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'agent' => $agent,
                    'stats' => [
                        'commissions' => [
                            'pending' => $totalPendingCommission,
                            'paid' => $totalPaidCommission,
                            'total' => $totalPendingCommission + $totalPaidCommission,
                        ],
                        'orders' => $orderStats,
                    ]
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve agent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new agent.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Only admin and staff can create agents
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to create agents',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'fixed_commission' => 'required|numeric|min:0',
                'percentage_commission' => 'required|numeric|min:0',
                'status' => 'required|in:active,inactive',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'agent',
            ]);

            // Create agent
            $agent = Agent::create([
                'user_id' => $user->id,
                'fixed_commission' => $request->fixed_commission,
                'percentage_commission' => $request->percentage_commission,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);

            DB::commit();

            // Load user relationship
            $agent->load('user');

            return response()->json([
                'status' => 'success',
                'message' => 'Agent created successfully',
                'data' => $agent,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create agent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an agent.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $agent = Agent::with('user')->findOrFail($id);

            // Check permissions
            if ($user->role !== 'hq' && $user->role !== 'staff') {
                if ($user->role !== 'agent' || ($user->agent && $user->agent->id !== $agent->id)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to update this agent',
                    ], 403);
                }
                
                // If agent is updating themselves, they can only update notes
                if ($user->role === 'agent' && $user->agent && $user->agent->id === $agent->id) {
                    $validator = Validator::make($request->all(), [
                        'notes' => 'nullable|string',
                    ]);
                    
                    if ($validator->fails()) {
                        return response()->json(['errors' => $validator->errors()], 422);
                    }
                    
                    $agent->update([
                        'notes' => $request->notes,
                    ]);
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Agent updated successfully',
                        'data' => $agent->fresh('user'),
                    ]);
                }
            }

            // Admin/Staff validation
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|string|email|max:255|unique:users,email,' . $agent->user_id,
                'password' => 'nullable|string|min:8',
                'fixed_commission' => 'nullable|numeric|min:0',
                'percentage_commission' => 'nullable|numeric|min:0',
                'status' => 'nullable|in:active,inactive',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            DB::beginTransaction();

            // Update user
            if ($request->has('name') || $request->has('email') || $request->has('password')) {
                $userData = [];
                
                if ($request->has('name')) {
                    $userData['name'] = $request->name;
                }
                
                if ($request->has('email')) {
                    $userData['email'] = $request->email;
                }
                
                if ($request->has('password')) {
                    $userData['password'] = Hash::make($request->password);
                }
                
                $agent->user->update($userData);
            }

            // Update agent
            $agentData = $request->only([
                'fixed_commission', 'percentage_commission', 'status', 'notes'
            ]);
            
            if (!empty($agentData)) {
                $agent->update($agentData);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Agent updated successfully',
                'data' => $agent->fresh('user'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update agent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle agent status.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            // Only admin and staff can toggle agent status
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to toggle agent status',
                ], 403);
            }

            $agent = Agent::findOrFail($id);
            $newStatus = $agent->status === 'active' ? 'inactive' : 'active';

            $agent->update([
                'status' => $newStatus,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Agent status toggled to ' . $newStatus,
                'data' => $agent->fresh('user'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle agent status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get agent commission summary.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function commissionSummary(Request $request, $id)
    {
        try {
            $user = $request->user();
            $agent = Agent::findOrFail($id);
            
            // Check permissions
            if ($user->role === 'client') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clients cannot access agent commission data',
                ], 403);
            }
            
            if ($user->role === 'agent' && $user->agent && $user->agent->id !== $agent->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only view your own commission data',
                ], 403);
            }
            
            // Get monthly commission data for the last 12 months
            $endDate = now();
            $startDate = $endDate->copy()->subMonths(11)->startOfMonth();
            
            $monthlyCommissions = DB::table('commissions')
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('SUM(CASE WHEN status = "pending" THEN total_commission ELSE 0 END) as pending_amount'),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN total_commission ELSE 0 END) as paid_amount'),
                    DB::raw('COUNT(*) as total_count')
                )
                ->where('agent_id', $id)
                ->where('created_at', '>=', $startDate)
                ->groupBy('month')
                ->orderBy('month')
                ->get();
                
            // Get total commission stats
            $totalStats = DB::table('commissions')
                ->select(
                    DB::raw('SUM(CASE WHEN status = "pending" THEN total_commission ELSE 0 END) as total_pending'),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN total_commission ELSE 0 END) as total_paid'),
                    DB::raw('COUNT(*) as total_count')
                )
                ->where('agent_id', $id)
                ->first();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'agent' => $agent->load('user'),
                    'monthly_summary' => $monthlyCommissions,
                    'total_summary' => $totalStats,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve commission summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Assign a commission type to an agent.
     *
     * @param Request $request
     * @param int $agentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignCommissionType(Request $request, $agentId)
    {
        try {
            $user = $request->user();
            
            // Only admin and staff can assign commission types
            if ($user->role !== 'hq' && $user->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to assign commission types',
                ], 403);
            }
            
            $agent = Agent::findOrFail($agentId);
            
            $validator = Validator::make($request->all(), [
                'commission_type_id' => 'required|exists:commission_types,id',
                'fixed_amount_override' => 'nullable|numeric|min:0',
                'percentage_rate_override' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
                'notes' => 'nullable|string|max:500',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            // Check if the commission type is already assigned
            $existing = $agent->commissionTypes()->where('commission_type_id', $request->commission_type_id)->first();
            
            if ($existing) {
                // Update the pivot data
                $agent->commissionTypes()->updateExistingPivot(
                    $request->commission_type_id,
                    [
                        'fixed_amount_override' => $request->fixed_amount_override,
                        'percentage_rate_override' => $request->percentage_rate_override,
                        'is_active' => $request->has('is_active') ? $request->is_active : true,
                        'notes' => $request->notes,
                    ]
                );
                
                $message = 'Commission type assignment updated successfully';
            } else {
                // Assign the commission type
                $agent->commissionTypes()->attach(
                    $request->commission_type_id,
                    [
                        'fixed_amount_override' => $request->fixed_amount_override,
                        'percentage_rate_override' => $request->percentage_rate_override,
                        'is_active' => $request->has('is_active') ? $request->is_active : true,
                        'notes' => $request->notes,
                    ]
                );
                
                $message = 'Commission type assigned successfully';
            }
            
            // Get the updated pivot data
            $assignment = $agent->commissionTypes()
                ->where('commission_type_id', $request->commission_type_id)
                ->first()
                ->pivot;
            
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $assignment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign commission type',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

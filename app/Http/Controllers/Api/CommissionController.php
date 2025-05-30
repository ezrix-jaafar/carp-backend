<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Commission;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommissionController extends Controller
{
    /**
     * Get all commissions based on user role.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $commissions = [];
            
            // Filter commissions based on user role
            if ($user->role === 'hq' || $user->role === 'staff') {
                // Admin can see all commissions
                $commissions = Commission::with(['agent.user', 'invoice.order.client.user'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            } elseif ($user->role === 'agent' && $user->agent) {
                // Agents can only see their own commissions
                $commissions = Commission::with(['invoice.order.client.user'])
                    ->where('agent_id', $user->agent->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            } elseif ($user->role === 'client') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clients cannot access commission data',
                ], 403);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $commissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve commissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific commission.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $commission = Commission::with(['agent.user', 'invoice.order.client.user'])
                ->findOrFail($id);

            // Check if user has permission to view this commission
            if ($user->role === 'agent' && $user->agent && $user->agent->id !== $commission->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this commission',
                ], 403);
            } elseif ($user->role === 'client') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clients cannot access commission data',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $commission,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve commission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new commission.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Only admin and staff can create commissions
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to create commissions',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'agent_id' => 'required|exists:agents,id',
                'invoice_id' => 'required|exists:invoices,id',
                'fixed_amount' => 'nullable|numeric|min:0',
                'percentage' => 'nullable|numeric|min:0',
                'total_commission' => 'nullable|numeric|min:0',
                'status' => 'required|in:pending,paid',
                'paid_at' => 'nullable|required_if:status,paid|date',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $invoice = Invoice::findOrFail($request->invoice_id);
            $agent = Agent::findOrFail($request->agent_id);

            // Check if commission already exists for this invoice
            $existingCommission = Commission::where('invoice_id', $invoice->id)->first();
            if ($existingCommission) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A commission already exists for this invoice',
                ], 422);
            }

            // Calculate commission amount if not provided
            $fixedAmount = $request->fixed_amount ?? $agent->fixed_commission;
            $percentage = $request->percentage ?? $agent->percentage_commission;
            
            $totalCommission = $request->total_commission;
            if ($totalCommission === null) {
                $totalCommission = Commission::calculateAmount(
                    $invoice->total_amount,
                    $fixedAmount,
                    $percentage
                );
            }

            // Create the commission
            $commission = Commission::create([
                'agent_id' => $request->agent_id,
                'invoice_id' => $request->invoice_id,
                'fixed_amount' => $fixedAmount,
                'percentage' => $percentage,
                'total_commission' => $totalCommission,
                'status' => $request->status,
                'paid_at' => $request->status === 'paid' ? ($request->paid_at ?? now()) : null,
                'notes' => $request->notes,
            ]);

            // Load relationships
            $commission->load(['agent.user', 'invoice.order.client.user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Commission created successfully',
                'data' => $commission,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create commission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a commission.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Only admin and staff can update commissions
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to update commissions',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'fixed_amount' => 'nullable|numeric|min:0',
                'percentage' => 'nullable|numeric|min:0',
                'total_commission' => 'nullable|numeric|min:0',
                'status' => 'nullable|in:pending,paid',
                'paid_at' => 'nullable|required_if:status,paid|date',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $commission = Commission::findOrFail($id);

            // Update the commission
            $updateData = $request->only([
                'fixed_amount', 'percentage', 'total_commission', 'status', 'notes'
            ]);

            // If status is being changed to paid, set paid_at
            if (isset($updateData['status']) && $updateData['status'] === 'paid' && $commission->status !== 'paid') {
                $updateData['paid_at'] = $request->paid_at ?? now();
            }

            // If status is being changed to pending, clear paid_at
            if (isset($updateData['status']) && $updateData['status'] === 'pending' && $commission->status === 'paid') {
                $updateData['paid_at'] = null;
            }

            // If fixed_amount or percentage changes, recalculate total_commission
            if ((isset($updateData['fixed_amount']) || isset($updateData['percentage'])) && !isset($updateData['total_commission'])) {
                $fixedAmount = $updateData['fixed_amount'] ?? $commission->fixed_amount;
                $percentage = $updateData['percentage'] ?? $commission->percentage;
                
                $updateData['total_commission'] = Commission::calculateAmount(
                    $commission->invoice->total_amount,
                    $fixedAmount,
                    $percentage
                );
            }

            $commission->update($updateData);

            // Load relationships
            $commission->load(['agent.user', 'invoice.order.client.user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Commission updated successfully',
                'data' => $commission,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update commission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a commission.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Only admin and staff can delete commissions
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to delete commissions',
                ], 403);
            }

            $commission = Commission::findOrFail($id);

            // Only allow deletion if status is pending
            if ($commission->status === 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete a paid commission',
                ], 422);
            }

            // Delete the commission
            $commission->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Commission deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete commission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get commissions for a specific agent.
     *
     * @param Request $request
     * @param int $agentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAgentCommissions(Request $request, $agentId)
    {
        try {
            $user = $request->user();
            
            // Check if user has permission to view this agent's commissions
            if ($user->role === 'agent' && $user->agent && $user->agent->id != $agentId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this agent\'s commissions',
                ], 403);
            } elseif ($user->role === 'client') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clients cannot access commission data',
                ], 403);
            }
            
            $agent = Agent::findOrFail($agentId);
            
            $commissions = Commission::with(['invoice.order.client.user'])
                ->where('agent_id', $agentId)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
            
            // Calculate commission statistics
            $totalPending = Commission::where('agent_id', $agentId)
                ->where('status', 'pending')
                ->sum('total_commission');
                
            $totalPaid = Commission::where('agent_id', $agentId)
                ->where('status', 'paid')
                ->sum('total_commission');
            
            $totalCommissions = $totalPending + $totalPaid;
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'agent' => $agent->load('user'),
                    'commissions' => $commissions,
                    'stats' => [
                        'total_pending' => $totalPending,
                        'total_paid' => $totalPaid,
                        'total_commissions' => $totalCommissions,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve agent commissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate potential commission for an agent.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateCommission(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'agent_id' => 'required|exists:agents,id',
                'amount' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = $request->user();
            $agent = Agent::findOrFail($request->agent_id);

            // Check permissions
            if ($user->role === 'client') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clients cannot access commission data',
                ], 403);
            }

            if ($user->role === 'agent' && $user->agent && $user->agent->id !== $agent->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only calculate commissions for yourself',
                ], 403);
            }

            // Calculate commission
            $amount = $request->amount;
            $fixedAmount = $agent->fixed_commission;
            $percentageRate = $agent->percentage_commission;
            $percentageAmount = ($amount * $percentageRate) / 100;
            $totalCommission = $fixedAmount + $percentageAmount;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'agent_id' => $agent->id,
                    'invoice_amount' => $amount,
                    'fixed_amount' => $fixedAmount,
                    'percentage_rate' => $percentageRate,
                    'percentage_amount' => $percentageAmount,
                    'total_commission' => $totalCommission,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate commission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

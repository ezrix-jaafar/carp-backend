<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Get all clients.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Only HQ and staff can view all clients. 
            // Agents can see clients whose orders they handled.
            if ($user->role === 'client') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Clients cannot access other client data',
                ], 403);
            }
            
            $query = Client::with('user');
            
            // Apply filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            if ($request->has('city')) {
                $query->where('city', $request->city);
            }
            
            if ($request->has('state')) {
                $query->where('state', $request->state);
            }
            
            // For agents, only show clients whose orders they handled
            if ($user->role === 'agent' && $user->agent) {
                $query->whereHas('orders', function ($q) use ($user) {
                    $q->where('agent_id', $user->agent->id);
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Map front-end sort fields to actual database fields if needed
            $fieldMap = [
                'name' => 'users.name',
                'email' => 'users.email',
                'city' => 'clients.city',
                'state' => 'clients.state',
                'created_at' => 'clients.created_at',
            ];
            
            $dbSortField = $fieldMap[$sortField] ?? 'clients.created_at';
            
            if (in_array($sortField, ['name', 'email'])) {
                $query->join('users', 'clients.user_id', '=', 'users.id')
                      ->select('clients.*')
                      ->orderBy($dbSortField, $sortDirection);
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
            
            $clients = $query->paginate($request->input('per_page', 15));
            
            return response()->json([
                'status' => 'success',
                'data' => $clients,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve clients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific client.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $client = Client::with(['user', 'orders.agent.user', 'orders.carpets', 'orders.invoice.payments'])
                ->findOrFail($id);
            
            // Check permissions
            if ($user->role === 'client') {
                if ($user->client && $user->client->id === $client->id) {
                    // Client can view their own data
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only view your own client data',
                    ], 403);
                }
            } elseif ($user->role === 'agent' && $user->agent) {
                // Agents can only view clients whose orders they handled
                $hasHandledOrder = $client->orders()
                    ->where('agent_id', $user->agent->id)
                    ->exists();
                
                if (!$hasHandledOrder) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view this client',
                    ], 403);
                }
            }
            
            // Get order statistics
            $orderStats = $client->orders()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            // Get payment statistics
            $totalPaid = 0;
            $totalPending = 0;
            
            foreach ($client->orders as $order) {
                if ($order->invoice) {
                    foreach ($order->invoice->payments as $payment) {
                        if ($payment->status === 'completed') {
                            $totalPaid += $payment->amount;
                        } else {
                            $totalPending += $payment->amount;
                        }
                    }
                }
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'client' => $client,
                    'stats' => [
                        'orders' => $orderStats,
                        'payments' => [
                            'total_paid' => $totalPaid,
                            'total_pending' => $totalPending,
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new client.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Only admin and staff can create clients directly
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to create clients',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
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
                'role' => 'client',
            ]);

            // Create client
            $client = Client::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
            ]);

            DB::commit();

            // Load user relationship
            $client->load('user');

            return response()->json([
                'status' => 'success',
                'message' => 'Client created successfully',
                'data' => $client,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a client.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $client = Client::with('user')->findOrFail($id);

            // Check permissions
            if ($user->role === 'client') {
                if ($user->client && $user->client->id === $client->id) {
                    // Client can update their own data, but only certain fields
                    $validator = Validator::make($request->all(), [
                        'phone' => 'nullable|string|max:20',
                        'address' => 'nullable|string|max:255',
                        'city' => 'nullable|string|max:100',
                        'state' => 'nullable|string|max:100',
                    ]);
                    
                    if ($validator->fails()) {
                        return response()->json(['errors' => $validator->errors()], 422);
                    }
                    
                    $client->update($request->only([
                        'phone', 'address', 'city', 'state'
                    ]));
                    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Client updated successfully',
                        'data' => $client->fresh('user'),
                    ]);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only update your own client data',
                    ], 403);
                }
            } elseif ($user->role === 'agent') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Agents cannot update client data',
                ], 403);
            }

            // Admin/Staff validation
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|string|email|max:255|unique:users,email,' . $client->user_id,
                'password' => 'nullable|string|min:8',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
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
                
                $client->user->update($userData);
            }

            // Update client
            $clientData = $request->only([
                'phone', 'address', 'city', 'state'
            ]);
            
            if (!empty($clientData)) {
                $client->update($clientData);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Client updated successfully',
                'data' => $client->fresh('user'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get client order history.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderHistory(Request $request, $id)
    {
        try {
            $user = $request->user();
            $client = Client::findOrFail($id);
            
            // Check permissions
            if ($user->role === 'client') {
                if ($user->client && $user->client->id !== $client->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only view your own order history',
                    ], 403);
                }
            } elseif ($user->role === 'agent' && $user->agent) {
                // Agents can only view order history for clients whose orders they handled
                $hasHandledOrder = $client->orders()
                    ->where('agent_id', $user->agent->id)
                    ->exists();
                
                if (!$hasHandledOrder) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view this client\'s order history',
                    ], 403);
                }
            }
            
            $orders = $client->orders()
                ->with(['carpets', 'agent.user', 'invoice.payments'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 10));
            
            // Get order statistics
            $orderStats = [
                'total_orders' => $client->orders()->count(),
                'total_carpets' => $client->orders()->sum('total_carpets'),
                'completed_orders' => $client->orders()->where('status', 'completed')->count(),
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'client' => $client->load('user'),
                    'orders' => $orders,
                    'stats' => $orderStats,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get client payment history.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentHistory(Request $request, $id)
    {
        try {
            $user = $request->user();
            $client = Client::findOrFail($id);
            
            // Check permissions
            if ($user->role === 'client') {
                if ($user->client && $user->client->id !== $client->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only view your own payment history',
                    ], 403);
                }
            } elseif ($user->role === 'agent' && $user->agent) {
                // Agents can only view payment history for clients whose orders they handled
                $hasHandledOrder = $client->orders()
                    ->where('agent_id', $user->agent->id)
                    ->exists();
                
                if (!$hasHandledOrder) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view this client\'s payment history',
                    ], 403);
                }
            }
            
            $payments = DB::table('payments')
                ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                ->join('orders', 'invoices.order_id', '=', 'orders.id')
                ->where('orders.client_id', $id)
                ->select('payments.*', 'invoices.invoice_number', 'orders.reference_number')
                ->orderBy('payments.created_at', 'desc')
                ->paginate($request->input('per_page', 10));
            
            // Get payment statistics
            $paymentStats = [
                'total_paid' => DB::table('payments')
                    ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                    ->join('orders', 'invoices.order_id', '=', 'orders.id')
                    ->where('orders.client_id', $id)
                    ->where('payments.status', 'completed')
                    ->sum('payments.amount'),
                'total_pending' => DB::table('payments')
                    ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                    ->join('orders', 'invoices.order_id', '=', 'orders.id')
                    ->where('orders.client_id', $id)
                    ->where('payments.status', 'pending')
                    ->sum('payments.amount'),
                'payment_count' => DB::table('payments')
                    ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                    ->join('orders', 'invoices.order_id', '=', 'orders.id')
                    ->where('orders.client_id', $id)
                    ->count(),
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'client' => $client->load('user'),
                    'payments' => $payments,
                    'stats' => $paymentStats,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

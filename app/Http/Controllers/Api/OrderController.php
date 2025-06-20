<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\OrderStatusHistory;

class OrderController extends Controller
{
    /**
     * Get all orders based on user role.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $orders = [];
            
            // Filter orders based on user role
            if ($user->role === 'hq' || $user->role === 'staff') {
                // Admin can see all orders
                $orders = Order::with(['client.user', 'agent.user', 'carpets', 'invoice'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            } elseif ($user->role === 'agent' && $user->agent) {
                // Agents can only see their assigned orders
                $orders = Order::with(['client.user', 'carpets', 'invoice'])
                    ->where('agent_id', $user->agent->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            } elseif ($user->role === 'client' && $user->client) {
                // Clients can only see their own orders
                $orders = Order::with(['agent.user', 'carpets', 'invoice'])
                    ->where('client_id', $user->client->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $orders,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific order.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $order = Order::with(['client.user', 'agent.user', 'carpets', 'invoice.payments'])
                ->findOrFail($id);

            // Check if user has permission to view this order
            if ($user->role === 'client' && $user->client && $user->client->id !== $order->client_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this order',
                ], 403);
            }

            if ($user->role === 'agent' && $user->agent && $user->agent->id !== $order->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this order',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new order.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'client_id' => 'required_without:client_info|exists:clients,id',
                'agent_id' => 'nullable|exists:agents,id',
                'pickup_date' => 'nullable|date',
                'pickup_address_id' => 'required|exists:addresses,id',
                'delivery_date' => 'nullable|date|after_or_equal:pickup_date',
                'delivery_address_id' => 'nullable|exists:addresses,id',
                'notes' => 'nullable|string',
                'total_carpets' => 'required|integer|min:1',
                'client_info' => 'required_without:client_id',
                'client_info.name' => 'required_with:client_info|string',
                'client_info.email' => 'required_with:client_info|email|unique:users,email',
                'client_info.phone' => 'required_with:client_info|string',
                'client_info.address' => 'required_with:client_info|string',
                'client_info.city' => 'required_with:client_info|string',
                'client_info.state' => 'required_with:client_info|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // If client_info is provided, create a new client
            $clientId = $request->client_id;
            if ($request->has('client_info')) {
                // Create a new user with client role
                $user = User::create([
                    'name' => $request->client_info['name'],
                    'email' => $request->client_info['email'],
                    'password' => bcrypt('password'), // Temporary password, should be changed
                    'role' => 'client',
                ]);

                // Create a new client record
                $client = Client::create([
                    'user_id' => $user->id,
                    'phone' => $request->client_info['phone'],
                    'address' => $request->client_info['address'],
                    'city' => $request->client_info['city'],
                    'state' => $request->client_info['state'],
                ]);

                $clientId = $client->id;
            }

            // Auto-assign agent if not specified
            $agentId = $request->agent_id;
            if (!$agentId && ($request->user()->role === 'hq' || $request->user()->role === 'staff')) {
                // Get random active agent with the least number of active orders
                $agent = Agent::where('status', 'active')
                    ->withCount(['orders' => function ($query) {
                        $query->whereIn('status', ['pending', 'assigned', 'picked_up', 'in_cleaning']);
                    }])
                    ->orderBy('orders_count', 'asc')
                    ->first();

                if ($agent) {
                    $agentId = $agent->id;
                }
            }

            // Generate reference number
            $referenceNumber = Order::generateReferenceNumber();

            // Create the order
            $order = Order::create([
                'client_id' => $clientId,
                'agent_id' => $agentId,
                'status' => $agentId ? 'assigned' : 'pending',
                'pickup_date' => $request->pickup_date,
                'pickup_address_id' => $request->pickup_address_id,
                'delivery_date' => $request->delivery_date,
                'delivery_address_id' => $request->delivery_address_id ?: $request->pickup_address_id,
                'notes' => $request->notes,
                'total_carpets' => $request->total_carpets,
                'reference_number' => $referenceNumber,
            ]);

            // Load relationships
            $order->load(['client.user', 'agent.user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => $order,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an order.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);
            $user = $request->user();

            // Check if user has permission to update this order
            if ($user->role === 'client' && $user->client && $user->client->id !== $order->client_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to update this order',
                ], 403);
            }

            if ($user->role === 'agent' && $user->agent && $user->agent->id !== $order->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to update this order',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'agent_id' => 'nullable|exists:agents,id',
                'status' => 'nullable|in:pending,assigned,agent_accepted,agent_rejected,picked_up,in_cleaning,hq_inspection,cleaned,delivered,completed,cancelled',
                'pickup_date' => 'nullable|date',
                'pickup_address_id' => 'nullable|exists:addresses,id',
                'delivery_date' => 'nullable|date|after_or_equal:pickup_date',
                'delivery_address_id' => 'nullable|exists:addresses,id',
                'notes' => 'nullable|string',
                'total_carpets' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Check what fields can be updated based on user role
            $allowedFields = [];
            if ($user->role === 'hq' || $user->role === 'staff') {
                // Admin can update all fields
                $allowedFields = [
                    'agent_id', 'status', 'pickup_date', 'pickup_address_id',
                    'delivery_date', 'delivery_address_id', 'notes', 'total_carpets'
                ];
            } elseif ($user->role === 'agent') {
                // Agent can update status and notes
                $allowedFields = ['status', 'notes'];
                
                // Enforce status transition rules for agents
                if ($request->has('status')) {
                    $currentStatus = $order->status;
                    $newStatus = $request->status;
                    
                    $validTransitions = [
                        // Agent can accept or reject an assigned order
                        'awaiting_agent' => ['agent_accepted', 'agent_rejected'],
                    'assigned' => ['agent_accepted', 'agent_rejected'],
                        'agent_accepted' => ['picked_up'],
                        'picked_up' => ['in_cleaning'],
                        'in_cleaning' => ['cleaned'],
                        'cleaned' => ['delivered'],
                        'delivered' => ['completed'],
                    ];
                    
                    if (!isset($validTransitions[$currentStatus]) || 
                        !in_array($newStatus, $validTransitions[$currentStatus])) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Invalid status transition',
                        ], 422);
                    }
                }
            } elseif ($user->role === 'client') {
                // Client can only update pickup details before order is picked up
                if ($order->status === 'pending' || $order->status === 'assigned') {
                    $allowedFields = ['pickup_date', 'pickup_address_id', 'notes'];
                } else {
                    $allowedFields = ['notes'];
                }
            }

            // Update only allowed fields
            $updateData = [];
            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            // Perform update if there are any allowed fields to update
            if (!empty($updateData)) {
                $order->update($updateData);
                // Post-update logging
                if (isset($oldStatus) && $oldStatus !== $order->status) {
                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => $order->status,
                        'changed_by' => $request->user()->id ?? null,
                    ]);
                }
            }

            // If agent was assigned, update status
            if (isset($updateData['agent_id']) && $updateData['agent_id'] && $order->status === 'pending') {
                $order->status = 'assigned';
                $order->save();
            }

            // Load relationships
            $order->load(['client.user', 'agent.user', 'carpets', 'invoice']);

            return response()->json([
                'status' => 'success',
                'message' => 'Order updated successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an order.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Only admin can delete orders
            if ($user->role !== 'hq' && $user->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to delete orders',
                ], 403);
            }
            
            $order = Order::findOrFail($id);
            
            // Check if order can be deleted (only pending orders with no carpets can be deleted)
            if ($order->status !== 'pending' || $order->carpets()->count() > 0 || $order->invoice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This order cannot be deleted as it has already been processed or has carpets/invoice associated with it',
                ], 422);
            }
            
            $order->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get orders for a specific client.
     *
     * @param Request $request
     * @param int $clientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientOrders(Request $request, $clientId)
    {
        try {
            $user = $request->user();
            
            // Check if user has permission to view this client's orders
            if ($user->role === 'client' && $user->client && $user->client->id != $clientId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this client\'s orders',
                ], 403);
            }
            
            $client = Client::findOrFail($clientId);
            
            $orders = Order::with(['agent.user', 'carpets', 'invoice'])
                ->where('client_id', $clientId)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'client' => $client->load('user'),
                    'orders' => $orders,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve client orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get orders for a specific agent.
     *
     * @param Request $request
     * @param int $agentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAgentOrders(Request $request, $agentId)
    {
        try {
            $user = $request->user();
            
            // Check if user has permission to view this agent's orders
            if ($user->role === 'agent' && $user->agent && $user->agent->id != $agentId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this agent\'s orders',
                ], 403);
            }
            
            $agent = Agent::findOrFail($agentId);
            
            $orders = Order::with(['client.user', 'carpets', 'invoice'])
                ->where('agent_id', $agentId)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'agent' => $agent->load('user'),
                    'orders' => $orders,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve agent orders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign an agent to an order.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignAgent(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Only HQ or staff can assign agents
            if ($user->role !== 'hq' && $user->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to assign agents',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'agent_id' => 'required|exists:agents,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $order = Order::findOrFail($id);

            // Only assign if order not completed or cancelled
            if (in_array($order->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This order cannot be assigned',
                ], 422);
            }

            $order->agent_id = $request->agent_id;
            $order->status = 'assigned';
            $order->save();

            // Push notification to agent via FCM
            \App\Services\FCMPushService::sendOrderAssigned($order);

            return response()->json([
                'status' => 'success',
                'message' => 'Agent assigned successfully',
                'data' => $order->load(['agent.user']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign agent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update order status (dedicated endpoint).
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = $request->user();
            $order = Order::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:agent_accepted,agent_rejected,picked_up,in_cleaning,cleaned,delivered,completed,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $newStatus = $request->status;
            $currentStatus = $order->status;

            // Permissions and transition validation
            if ($user->role === 'agent') {
                if (!$user->agent || $user->agent->id !== $order->agent_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to update this order',
                    ], 403);
                }

                $validTransitions = [
                    'awaiting_agent' => ['agent_accepted', 'agent_rejected'],
                    'assigned' => ['agent_accepted', 'agent_rejected'],
                    'agent_accepted' => ['picked_up'],
                    'picked_up' => ['in_cleaning'],
                    'in_cleaning' => ['cleaned'],
                    'cleaned' => ['delivered'],
                    'delivered' => ['completed'],
                ];

                if (!isset($validTransitions[$currentStatus]) ||
                    !in_array($newStatus, $validTransitions[$currentStatus])) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid status transition',
                    ], 422);
                }
            } elseif (!in_array($user->role, ['hq', 'staff'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            if ($newStatus === 'agent_rejected') {
                $order->agent_id = null; // free up order for reassignment
            }

            $order->status = $newStatus;
            $order->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update order status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

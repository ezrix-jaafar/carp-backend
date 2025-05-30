<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    /**
     * Get all invoices based on user role.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $invoices = [];
            
            // Filter invoices based on user role
            if ($user->role === 'hq' || $user->role === 'staff') {
                // Admin can see all invoices
                $invoices = Invoice::with(['order.client.user', 'order.agent.user', 'payments'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            } elseif ($user->role === 'agent' && $user->agent) {
                // Agents can only see invoices for their orders
                $invoices = Invoice::with(['order.client.user', 'payments'])
                    ->whereHas('order', function ($query) use ($user) {
                        $query->where('agent_id', $user->agent->id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            } elseif ($user->role === 'client' && $user->client) {
                // Clients can only see their own invoices
                $invoices = Invoice::with(['order.agent.user', 'payments'])
                    ->whereHas('order', function ($query) use ($user) {
                        $query->where('client_id', $user->client->id);
                    })
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
                'data' => $invoices,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific invoice.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $invoice = Invoice::with(['order.client.user', 'order.agent.user', 'order.carpets', 'payments', 'commission'])
                ->findOrFail($id);

            // Check if user has permission to view this invoice
            if ($user->role === 'client' && $user->client) {
                $clientId = $invoice->order->client_id;
                if ($user->client->id !== $clientId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view this invoice',
                    ], 403);
                }
            } elseif ($user->role === 'agent' && $user->agent) {
                $agentId = $invoice->order->agent_id;
                if ($user->agent->id !== $agentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view this invoice',
                    ], 403);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new invoice.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Only admin and staff can create invoices
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to create invoices',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'total_amount' => 'required|numeric|min:0',
                'status' => 'required|in:pending,paid,overdue,cancelled',
                'issued_at' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issued_at',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $order = Order::findOrFail($request->order_id);

            // Check if order already has an invoice
            if ($order->invoice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This order already has an invoice',
                ], 422);
            }

            // Generate invoice number
            $invoiceNumber = Invoice::generateInvoiceNumber();

            // Create the invoice
            $invoice = Invoice::create([
                'order_id' => $request->order_id,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $request->total_amount,
                'status' => $request->status,
                'issued_at' => $request->issued_at,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
            ]);

            // Load relationships
            $invoice->load(['order.client.user', 'order.agent.user', 'order.carpets']);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice created successfully',
                'data' => $invoice,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an invoice.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Only admin and staff can update invoices
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to update invoices',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'total_amount' => 'nullable|numeric|min:0',
                'status' => 'nullable|in:pending,paid,overdue,cancelled',
                'issued_at' => 'nullable|date',
                'due_date' => 'nullable|date|after_or_equal:issued_at',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $invoice = Invoice::findOrFail($id);

            // Update the invoice
            $invoice->update($request->only([
                'total_amount', 'status', 'issued_at', 'due_date', 'notes'
            ]));

            // Load relationships
            $invoice->load(['order.client.user', 'order.agent.user', 'order.carpets', 'payments']);

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice updated successfully',
                'data' => $invoice,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an invoice.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Only admin and staff can delete invoices
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to delete invoices',
                ], 403);
            }

            $invoice = Invoice::with('payments')->findOrFail($id);

            // Check if invoice has payments
            if ($invoice->payments->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete invoice with payments',
                ], 422);
            }

            // Delete the invoice
            $invoice->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete invoice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate the total for an order.
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateTotal(Request $request, $orderId)
    {
        try {
            $order = Order::with('carpets')->findOrFail($orderId);
            
            // Check if user has permission to calculate total for this order
            $user = $request->user();
            if ($user->role === 'client' && $user->client && $user->client->id !== $order->client_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to calculate total for this order',
                ], 403);
            }
            
            if ($user->role === 'agent' && $user->agent && $user->agent->id !== $order->agent_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to calculate total for this order',
                ], 403);
            }
            
            // Calculate total based on carpets
            $totalAmount = 0;
            foreach ($order->carpets as $carpet) {
                // Base pricing is calculated on area (in square meters) * rate
                $dimensions = $carpet->dimensions;
                $area = $dimensions['area'] ?? ($dimensions['width'] * $dimensions['length']);
                
                // Base rate is RM15 per square meter
                $basePrice = $area * 15;
                
                // Add additional price based on carpet type
                switch ($carpet->type) {
                    case 'silk':
                        $typeMultiplier = 1.5; // 50% premium for luxury carpets
                        break;
                    case 'wool':
                        $typeMultiplier = 1.2; // 20% premium for premium carpets
                        break;
                    default:
                        $typeMultiplier = 1.0; // Standard rate for regular carpets
                }
                
                $carpetPrice = $basePrice * $typeMultiplier + $carpet->additional_charges;
                $totalAmount += $carpetPrice;
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'order_id' => $order->id,
                    'total_amount' => round($totalAmount, 2),
                    'carpet_count' => $order->carpets->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate total',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ToyyibPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $toyyibPayService;

    /**
     * Create a new controller instance.
     *
     * @param ToyyibPayService $toyyibPayService
     */
    public function __construct(ToyyibPayService $toyyibPayService)
    {
        $this->toyyibPayService = $toyyibPayService;
    }

    /**
     * Get all payments based on user role.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $payments = [];
            
            // Filter payments based on user role
            if ($user->role === 'hq' || $user->role === 'staff') {
                // Admin can see all payments
                $payments = Payment::with(['invoice.order.client.user', 'invoice.order.agent.user'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            } elseif ($user->role === 'agent' && $user->agent) {
                // Agents can only see payments for their invoices
                $payments = Payment::with(['invoice.order.client.user'])
                    ->whereHas('invoice.order', function ($query) use ($user) {
                        $query->where('agent_id', $user->agent->id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            } elseif ($user->role === 'client' && $user->client) {
                // Clients can only see their own payments
                $payments = Payment::with(['invoice.order.agent.user'])
                    ->whereHas('invoice.order', function ($query) use ($user) {
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
                'data' => $payments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific payment.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $payment = Payment::with(['invoice.order.client.user', 'invoice.order.agent.user'])
                ->findOrFail($id);

            // Check if user has permission to view this payment
            if ($user->role === 'client' && $user->client) {
                $clientId = $payment->invoice->order->client_id;
                if ($user->client->id !== $clientId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view this payment',
                    ], 403);
                }
            } elseif ($user->role === 'agent' && $user->agent) {
                $agentId = $payment->invoice->order->agent_id;
                if ($user->agent->id !== $agentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view this payment',
                    ], 403);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new payment using ToyyibPay.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|exists:invoices,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = $request->user();
            $invoice = Invoice::with(['order.client.user', 'order.agent.user'])->findOrFail($request->invoice_id);

            // Check if user has permission to create payment for this invoice
            if ($user->role === 'client' && $user->client) {
                $clientId = $invoice->order->client_id;
                if ($user->client->id !== $clientId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to create payment for this invoice',
                    ], 403);
                }
            } elseif ($user->role === 'agent') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Agents cannot create payments',
                ], 403);
            }

            // Check if invoice is already paid
            if ($invoice->status === 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This invoice is already paid',
                ], 422);
            }

            // Create bill with ToyyibPay
            $billResponse = $this->toyyibPayService->createBill($invoice);

            if (!$billResponse['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create payment bill',
                    'details' => $billResponse,
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Payment bill created successfully',
                'data' => [
                    'bill_code' => $billResponse['bill_code'],
                    'payment_url' => $billResponse['payment_url'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check payment status with ToyyibPay.
     *
     * @param Request $request
     * @param string $billCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPaymentStatus(Request $request, $billCode)
    {
        try {
            $payment = Payment::where('bill_code', $billCode)->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment not found',
                ], 404);
            }

            $user = $request->user();

            // Check if user has permission to check this payment
            if ($user->role === 'client' && $user->client) {
                $clientId = $payment->invoice->order->client_id;
                if ($user->client->id !== $clientId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to check this payment',
                    ], 403);
                }
            } elseif ($user->role === 'agent' && $user->agent) {
                $agentId = $payment->invoice->order->agent_id;
                if ($user->agent->id !== $agentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to check this payment',
                    ], 403);
                }
            }

            // Check status with ToyyibPay
            $statusResponse = $this->toyyibPayService->getBillPaymentStatus($billCode);

            return response()->json([
                'status' => 'success',
                'data' => $statusResponse,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually update a payment (admin only).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Only admin and staff can manually update payments
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to update payments',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:pending,completed',
                'payment_method' => 'nullable|in:toyyibpay,bank_transfer,cash',
                'transaction_reference' => 'nullable|string',
                'paid_at' => 'nullable|date',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $payment = Payment::with('invoice')->findOrFail($id);

            // Update the payment
            $payment->update($request->only([
                'status', 'payment_method', 'transaction_reference', 'paid_at', 'notes'
            ]));

            // If status is changed to completed, update the invoice status
            if ($request->status === 'completed') {
                $payment->paid_at = $request->paid_at ?? now();
                $payment->save();

                // Update invoice status
                $payment->invoice->status = 'paid';
                $payment->invoice->save();

                // Generate commission if applicable
                $this->generateCommission($payment);
            }

            // Load relationships
            $payment->load(['invoice.order.client.user', 'invoice.order.agent.user']);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment updated successfully',
                'data' => $payment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process ToyyibPay webhook callback.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhookCallback(Request $request)
    {
        try {
            Log::info('ToyyibPay webhook received', $request->all());

            // Handle webhook
            $response = $this->toyyibPayService->handleWebhook($request->all());

            if ($response['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Webhook processed successfully',
                    'data' => $response,
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to process webhook',
                    'details' => $response,
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('ToyyibPay webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process webhook',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate commission for a completed payment.
     *
     * @param Payment $payment
     * @return void
     */
    private function generateCommission(Payment $payment)
    {
        try {
            $invoice = $payment->invoice;
            $order = $invoice->order;
            $agent = $order->agent;

            if ($agent) {
                // Check if commission already exists
                $existingCommission = Commission::where('invoice_id', $invoice->id)->first();

                if (!$existingCommission) {
                    Commission::createFromInvoice($agent, $invoice);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error generating commission', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get payments for a specific invoice.
     *
     * @param Request $request
     * @param int $invoiceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvoicePayments(Request $request, $invoiceId)
    {
        try {
            $user = $request->user();
            $invoice = Invoice::with('order')->findOrFail($invoiceId);
            
            // Check if user has permission to view payments for this invoice
            if ($user->role === 'client' && $user->client) {
                $clientId = $invoice->order->client_id;
                if ($user->client->id !== $clientId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view payments for this invoice',
                    ], 403);
                }
            } elseif ($user->role === 'agent' && $user->agent) {
                $agentId = $invoice->order->agent_id;
                if ($user->agent->id !== $agentId) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You do not have permission to view payments for this invoice',
                    ], 403);
                }
            }
            
            $payments = Payment::where('invoice_id', $invoiceId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $payments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve invoice payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ToyyibPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ToyyibPayWebhookController extends Controller
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
     * Handle ToyyibPay webhook callback.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Log the webhook data
            Log::info('ToyyibPay webhook received', $request->all());
            
            // Validate the webhook
            $billCode = $request->input('billcode');
            $status = $request->input('status');
            
            if (!$billCode) {
                Log::error('ToyyibPay webhook: Missing bill code');
                return response('Invalid webhook data', 400);
            }
            
            // Find the payment by bill code
            $payment = Payment::where('bill_code', $billCode)->first();
            
            if (!$payment) {
                Log::error('ToyyibPay webhook: Payment not found for bill code: ' . $billCode);
                return response('Payment not found', 404);
            }
            
            // Process based on payment status
            if ($status == '1') { // Successful payment
                // Update payment status
                $payment->status = 'completed';
                $payment->transaction_reference = $request->input('transaction_id') ?? $payment->transaction_reference;
                $payment->payment_details = json_encode($request->all());
                $payment->paid_at = now();
                $payment->save();
                
                // Update invoice status
                $invoice = $payment->invoice;
                $invoice->status = 'paid';
                $invoice->save();
                
                // Generate commission if applicable
                $this->generateCommission($payment);
                
                Log::info('ToyyibPay webhook: Payment completed for bill code: ' . $billCode);
                
                return response('Payment successfully processed', 200);
            } elseif ($status == '2') { // Pending payment
                // No action needed, payment is already in pending state
                Log::info('ToyyibPay webhook: Payment pending for bill code: ' . $billCode);
                return response('Payment is pending', 200);
            } elseif ($status == '3') { // Failed payment
                // Update payment status to reflect failure
                $payment->status = 'failed';
                $payment->payment_details = json_encode($request->all());
                $payment->save();
                
                Log::info('ToyyibPay webhook: Payment failed for bill code: ' . $billCode);
                
                return response('Payment failed', 200);
            } else {
                Log::warning('ToyyibPay webhook: Unknown payment status: ' . $status);
                return response('Unknown payment status', 400);
            }
        } catch (\Exception $e) {
            Log::error('ToyyibPay webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return response('Error processing webhook: ' . $e->getMessage(), 500);
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
                    // Create commission record
                    Commission::createFromInvoice($agent, $invoice);
                    
                    Log::info('Commission generated for agent ID: ' . $agent->id . ' from invoice: ' . $invoice->id);
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
     * Manually check and update payment status.
     * This endpoint can be used to force a status check for a payment.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAndUpdateStatus(Request $request)
    {
        try {
            // Only admin and staff can force status checks
            if ($request->user()->role !== 'hq' && $request->user()->role !== 'staff') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to perform this action',
                ], 403);
            }
            
            $billCode = $request->input('bill_code');
            
            if (!$billCode) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bill code is required',
                ], 400);
            }
            
            // Find the payment
            $payment = Payment::where('bill_code', $billCode)->first();
            
            if (!$payment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment not found',
                ], 404);
            }
            
            // Check status with ToyyibPay
            $statusResponse = $this->toyyibPayService->getBillPaymentStatus($billCode);
            
            if (!$statusResponse['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to check payment status',
                    'details' => $statusResponse,
                ], 500);
            }
            
            // Process based on payment status
            $paymentStatus = $statusResponse['status'] ?? null;
            
            if ($paymentStatus == '1') { // Successful payment
                // Update payment status
                $payment->status = 'completed';
                $payment->payment_details = json_encode($statusResponse);
                $payment->paid_at = now();
                $payment->save();
                
                // Update invoice status
                $invoice = $payment->invoice;
                $invoice->status = 'paid';
                $invoice->save();
                
                // Generate commission if applicable
                $this->generateCommission($payment);
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment status updated to completed',
                    'data' => $payment->fresh(['invoice']),
                ]);
            } elseif ($paymentStatus == '2') { // Pending payment
                // No action needed, payment is already in pending state
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment is still pending',
                    'data' => $payment,
                ]);
            } elseif ($paymentStatus == '3') { // Failed payment
                // Update payment status to reflect failure
                $payment->status = 'failed';
                $payment->payment_details = json_encode($statusResponse);
                $payment->save();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment status updated to failed',
                    'data' => $payment->fresh(['invoice']),
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unknown payment status',
                    'details' => $statusResponse,
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check and update payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

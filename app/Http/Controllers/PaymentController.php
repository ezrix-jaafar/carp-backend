<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ToyyibpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $toyyibPayService;

    public function __construct(ToyyibpayService $toyyibPayService)
    {
        $this->toyyibPayService = $toyyibPayService;
    }

    /**
     * Display the payment page for a specific invoice
     */
    public function showPaymentPage(Request $request, $invoiceId)
    {
        // Decrypt invoice ID if it's encrypted
        try {
            $id = decrypt($invoiceId);
        } catch (\Throwable $e) {
            $id = $invoiceId; // Use as-is if decryption fails
        }

        $invoice = Invoice::with(['order.client.user', 'items'])->find($id);

        if (!$invoice) {
            return view('payments.error', [
                'message' => 'Invoice not found.'
            ]);
        }

        // Don't allow payment for already paid or canceled invoices
        if ($invoice->status === 'paid') {
            return view('payments.already-paid', [
                'invoice' => $invoice
            ]);
        }

        if ($invoice->status === 'canceled') {
            return view('payments.error', [
                'message' => 'This invoice has been canceled.'
            ]);
        }

        // Get or create payment bill
        $payment = $invoice->payments()->where('status', 'pending')->latest()->first();

        if (!$payment || !isset($payment->payment_details['bill_code'])) {
            // Create a new bill
            $result = $this->toyyibPayService->createBill($invoice);

            if (!$result['success']) {
                Log::error('Failed to create payment bill', $result);
                return view('payments.error', [
                    'message' => 'Unable to create payment at this time. Please try again later.'
                ]);
            }
        } else {
            // Check status of existing bill
            $result = $this->toyyibPayService->getBillPaymentStatus(
                $payment->payment_details['bill_code']
            );

            // If the payment is already completed, redirect to success
            if ($result['success'] && $result['status'] === 'completed') {
                return redirect()->route('payments.success', ['invoice' => $invoiceId]);
            }
        }

        // Get the latest payment after potential creation
        $payment = $invoice->payments()->where('status', 'pending')->latest()->first();

        if (!$payment) {
            return view('payments.error', [
                'message' => 'Unable to process payment at this time. Please try again later.'
            ]);
        }

        return view('payments.checkout', [
            'invoice' => $invoice,
            'payment' => $payment,
            'paymentUrl' => $this->toyyibPayService->getPaymentUrl($payment->bill_code),
        ]);
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request, $invoiceId)
    {
        try {
            $id = decrypt($invoiceId);
        } catch (\Throwable $e) {
            $id = $invoiceId;
        }
        
        $invoice = Invoice::with(['order.client.user'])->find($id);
        
        if (!$invoice) {
            return view('payments.error', [
                'message' => 'Invoice not found.'
            ]);
        }
        
        // Process Toyyibpay response parameters
        $statusId = $request->get('status_id');
        $billCode = $request->get('billcode');
        $orderId = $request->get('order_id'); // This is our invoice number
        $msg = $request->get('msg');
        $transactionId = $request->get('transaction_id');
        
        // Log the callback
        Log::info('Payment callback', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status_id' => $statusId,
            'bill_code' => $billCode,
            'msg' => $msg,
            'transaction_id' => $transactionId
        ]);
        
        // If payment failed or was cancelled, show error/failure page
        if ($statusId != 1) {
            return view('payments.error', [
                'message' => 'Payment failed or was cancelled. (' . ($msg ?: 'Unknown error') . ')',
                'invoice' => $invoice,
                'status_id' => $statusId,
                'bill_code' => $billCode,
                'transaction_id' => $transactionId
            ]);
        }
        
        // Find the payment and update its status if needed
        $payment = $invoice->payments()
            ->where('bill_code', $billCode)
            ->first();
            
        if ($payment && $payment->status !== 'completed' && $statusId == 1) {
            $payment->update([
                'status' => 'completed',
                'transaction_reference' => $transactionId,
                'payment_details' => array_merge(
                    $payment->payment_details ?? [],
                    ['toyyibpay_response' => $request->all()]
                ),
                'paid_at' => now()
            ]);
            
            // Update invoice status
            $invoice->update(['status' => 'paid']);
            
            // Generate commission for the agent if applicable
            $this->generateCommissionForPayment($payment);
        }
        
        return view('payments.success', [
            'invoice' => $invoice
        ]);
    }

    /**
     * Handle ToyyibPay webhook
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        
        Log::info('ToyyibPay webhook received', ['data' => $data]);
        
        $result = $this->toyyibPayService->handleWebhook($data);
        
        return response()->json(['status' => $result['success'] ? 'success' : 'error']);
    }
    
    /**
     * Generate commission for a completed payment.
     *
     * @param Payment $payment
     * @return void
     */
    protected function generateCommissionForPayment(Payment $payment): void
    {
        try {
            $invoice = $payment->invoice;
            $order = $invoice->order;
            $agent = $order->agent;
            
            if ($agent) {
                \App\Models\Commission::createFromInvoice($agent, $invoice);
            }
        } catch (\Throwable $e) {
            Log::error('Error generating commission', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

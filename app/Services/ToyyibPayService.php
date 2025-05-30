<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ToyyibPayService
{
    protected string $env;
    protected string $apiUrl;
    protected string $paymentUrl;
    protected string $secretKey;
    protected string $categoryCode;

    /**
     * Constructor to set up environment variables for ToyyibPay.
     */
    public function __construct()
    {
        $this->env = config('services.toyyibpay.env', 'sandbox');
        $this->apiUrl = config("services.toyyibpay.{$this->env}.api_url");
        $this->paymentUrl = config("services.toyyibpay.{$this->env}.payment_url");
        $this->secretKey = config("services.toyyibpay.{$this->env}.secret_key");
        $this->categoryCode = config("services.toyyibpay.{$this->env}.category_code");
    }

    /**
     * Create a bill in ToyyibPay for the invoice.
     *
     * @param Invoice $invoice
     * @return array|null
     */
    public function createBill(Invoice $invoice): ?array
    {
        try {
            $order = $invoice->order;
            $client = $order->client;
            $user = $client->user;
            
            // Ensure the configuration is properly loaded
            if (empty($this->secretKey) || empty($this->categoryCode)) {
                Log::error('ToyyibPay configuration missing', [
                    'env' => $this->env,
                    'secret_key_exists' => !empty($this->secretKey),
                    'category_code_exists' => !empty($this->categoryCode),
                ]);
                
                // Reload configuration to ensure it's properly set
                $this->env = config('services.toyyibpay.env', 'sandbox');
                $this->apiUrl = config("services.toyyibpay.{$this->env}.api_url");
                $this->paymentUrl = config("services.toyyibpay.{$this->env}.payment_url");
                $this->secretKey = config("services.toyyibpay.{$this->env}.secret_key");
                $this->categoryCode = config("services.toyyibpay.{$this->env}.category_code");
                
                // Log the reloaded configuration
                Log::info('ToyyibPay configuration reloaded', [
                    'env' => $this->env,
                    'api_url' => $this->apiUrl,
                    'payment_url' => $this->paymentUrl,
                    'secret_key_exists' => !empty($this->secretKey),
                    'category_code_exists' => !empty($this->categoryCode),
                ]);
            }

            // Ensure bill name is within 30 characters (Toyyibpay limit)
            $billName = 'INV:' . $invoice->invoice_number;
            
            $billData = [
                'userSecretKey' => $this->secretKey,
                'categoryCode' => $this->categoryCode,
                'billName' => $billName, // This is now within 30 chars
                'billDescription' => 'Carpet Cleaning Services',
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => intval($invoice->total_amount * 100), // ToyyibPay expects amount in cents
                'billReturnUrl' => config('app.url') . '/pay/' . $invoice->id . '/success', // Match route: /pay/{invoice}/success
                'billCallbackUrl' => config('app.url') . '/api/payments/webhook',
                'billExternalReferenceNo' => $invoice->invoice_number,
                'billTo' => $user->name,
                'billEmail' => $user->email ?? 'customer@example.com',
                'billPhone' => $client->phone ?? '01234567890',
                'billSplitPayment' => 0,
                'billSplitPaymentArgs' => '',
                'billPaymentChannel' => '0',
                'billDisplayMerchant' => 1,
                'billContentEmail' => 'Thank you for your payment!',
                'billChargeToCustomer' => 1,
            ];

            // Log the exact URL and data being sent
            $url = rtrim($this->apiUrl, '/') . '/createBill';
            Log::info('ToyyibPay sending request to: ' . $url, [
                'data' => $billData,
                'secret_key_length' => strlen($billData['userSecretKey']),
                'category_code_length' => strlen($billData['categoryCode']),
            ]);
            
            // Use cURL directly to match the exact format in the API documentation
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_URL, $url);  
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $billData);
            
            $result = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            // Log the raw response
            Log::info('ToyyibPay raw response', [
                'status_code' => $httpCode,
                'body' => $result,
            ]);
            
            $responseData = json_decode($result, true);

            if ($httpCode == 200 && !empty($responseData)) {
                $billCode = $responseData[0]['BillCode'] ?? null;
                
                if ($billCode) {
                    // Create a payment record for tracking
                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $invoice->total_amount,
                        'status' => 'pending',
                        'payment_method' => 'toyyibpay',
                        'bill_code' => $billCode,
                        'payment_details' => [
                            'environment' => $this->env,
                            'bill_code' => $billCode,
                            'response' => $responseData[0],
                        ],
                    ]);
                    
                    return [
                        'success' => true,
                        'bill_code' => $billCode,
                        'payment_url' => rtrim($this->paymentUrl, '/') . '/' . $billCode,
                    ];
                }
            }
            
            Log::error('ToyyibPay createBill failed', [
                'invoice' => $invoice->invoice_number,
                'response' => $responseData,
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to create bill',
                'details' => $responseData,
            ];
        } catch (\Throwable $e) {
            Log::error('ToyyibPay createBill exception', [
                'invoice' => $invoice->invoice_number,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while creating the bill',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check the payment status for a bill.
     *
     * @param string $billCode
     * @return array
     */
    public function getBillPaymentStatus(string $billCode): array
    {
        try {
            $response = Http::post(rtrim($this->apiUrl, '/') . '/getBillPaymentStatus', [
                'userSecretKey' => $this->secretKey,
                'billCode' => $billCode,
            ]);
            
            $responseData = $response->json();
            
            if ($response->successful() && !empty($responseData)) {
                // Find and update the payment record
                $payment = Payment::where('bill_code', $billCode)->first();
                
                if ($payment) {
                    $paymentStatus = $responseData[0]['paid'] ?? 0;
                    $status = $paymentStatus ? 'completed' : 'pending';
                    
                    // Update payment details
                    $payment->update([
                        'status' => $status,
                        'transaction_reference' => $responseData[0]['billpaymentid'] ?? null,
                        'payment_details' => array_merge(
                            $payment->payment_details ?? [],
                            ['status_response' => $responseData[0]]
                        ),
                        'paid_at' => $paymentStatus ? now() : null,
                    ]);
                    
                    // If payment is completed, also update the invoice status
                    if ($status === 'completed') {
                        $payment->invoice->update(['status' => 'paid']);
                        
                        // Generate commission for the agent if applicable
                        $this->generateCommissionForPayment($payment);
                    }
                    
                    return [
                        'success' => true,
                        'status' => $status,
                        'payment' => $payment->refresh(),
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Payment record not found for bill code: ' . $billCode,
                    'response' => $responseData,
                ];
            }
            
            Log::error('ToyyibPay getBillPaymentStatus failed', [
                'bill_code' => $billCode,
                'response' => $responseData,
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to check payment status',
                'details' => $responseData,
            ];
        } catch (\Throwable $e) {
            Log::error('ToyyibPay getBillPaymentStatus exception', [
                'bill_code' => $billCode,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while checking payment status',
                'details' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Handle webhook callback from ToyyibPay.
     *
     * @param array $data
     * @return array
     */
    public function handleWebhook(array $data): array
    {
        try {
            $billCode = $data['billcode'] ?? null;
            
            if (!$billCode) {
                return [
                    'success' => false,
                    'message' => 'Bill code not provided in webhook data',
                ];
            }
            
            // Verify the payment status
            return $this->getBillPaymentStatus($billCode);
        } catch (\Throwable $e) {
            Log::error('ToyyibPay webhook exception', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while processing the webhook',
                'details' => $e->getMessage(),
            ];
        }
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
    
    /**
     * Get the payment URL for a bill code.
     *
     * @param string $billCode
     * @return string
     */
    public function getPaymentUrl(string $billCode): string
    {
        return rtrim($this->paymentUrl, '/') . '/' . $billCode;
    }
}

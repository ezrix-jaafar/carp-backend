<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get invoices that are either pending or paid
        $invoices = Invoice::whereIn('status', ['pending', 'paid'])->get();
        
        if ($invoices->isEmpty()) {
            $this->command->info('No invoices found for payments. Please run the InvoiceSeeder first.');
            return;
        }
        
        // Define payment methods
        $paymentMethods = ['toyyibpay', 'bank_transfer', 'cash'];
        
        // Create payments for the invoices
        foreach ($invoices as $invoice) {
            // If invoice is paid, create a completed payment
            if ($invoice->status === 'paid') {
                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
                $paidAt = Carbon::parse($invoice->issued_at)->addDays(rand(1, 7));
                
                // Create bill code for ToyyibPay payments
                $billCode = null;
                $paymentDetails = null;
                
                if ($paymentMethod === 'toyyibpay') {
                    $billCode = 'tb' . rand(100000, 999999);
                    $paymentDetails = [
                        'environment' => config('services.toyyibpay.env', 'sandbox'),
                        'bill_code' => $billCode,
                        'response' => [
                            'billpaymentid' => rand(10000000, 99999999),
                            'paid' => 'true',
                            'status' => 1,
                            'payment_channel' => rand(0, 3),
                        ],
                    ];
                } elseif ($paymentMethod === 'bank_transfer') {
                    $paymentDetails = [
                        'bank' => $this->getRandomBank(),
                        'reference' => 'TRF' . rand(10000, 99999),
                    ];
                }
                
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total_amount,
                    'status' => 'completed',
                    'payment_method' => $paymentMethod,
                    'transaction_reference' => $this->getTransactionReference($paymentMethod),
                    'bill_code' => $billCode,
                    'payment_details' => $paymentDetails,
                    'paid_at' => $paidAt,
                ]);
            } else {
                // For pending invoices, 50% chance to have a pending payment
                if (rand(0, 1) === 1) {
                    // Create a pending payment with ToyyibPay
                    $billCode = 'tb' . rand(100000, 999999);
                    $paymentDetails = [
                        'environment' => config('services.toyyibpay.env', 'sandbox'),
                        'bill_code' => $billCode,
                        'response' => [
                            'BillCode' => $billCode,
                            'CollectionId' => config('services.toyyibpay.sandbox.category_code', 'qwertyuiop'),
                            'billName' => 'Invoice for Order #' . $invoice->order->reference_number,
                            'billDescription' => 'Carpet Cleaning Services',
                            'billTo' => $invoice->order->client->user->name,
                            'billEmail' => $invoice->order->client->user->email,
                            'billStatus' => 0,
                        ],
                    ];
                    
                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $invoice->total_amount,
                        'status' => 'pending',
                        'payment_method' => 'toyyibpay',
                        'bill_code' => $billCode,
                        'payment_details' => $paymentDetails,
                    ]);
                }
            }
        }
    }
    
    /**
     * Generate a transaction reference based on payment method.
     *
     * @param string $method
     * @return string|null
     */
    private function getTransactionReference(string $method): ?string
    {
        switch ($method) {
            case 'toyyibpay':
                return 'TP' . rand(1000000, 9999999);
            case 'bank_transfer':
                return 'BTR' . rand(100000, 999999);
            case 'cash':
                return 'CSH' . rand(10000, 99999);
            default:
                return null;
        }
    }
    
    /**
     * Get a random Malaysian bank name.
     *
     * @return string
     */
    private function getRandomBank(): string
    {
        $banks = [
            'Maybank',
            'CIMB Bank',
            'Public Bank',
            'RHB Bank',
            'Hong Leong Bank',
            'AmBank',
            'Bank Islam',
            'Bank Rakyat',
        ];
        
        return $banks[array_rand($banks)];
    }
}

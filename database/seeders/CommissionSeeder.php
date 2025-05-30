<?php

namespace Database\Seeders;

use App\Models\Commission;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CommissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find all payments that are completed (which means the invoice is paid)
        $completedPayments = Payment::where('status', 'completed')->get();
        
        if ($completedPayments->isEmpty()) {
            $this->command->info('No completed payments found for commissions. Please run the PaymentSeeder first.');
            return;
        }
        
        foreach ($completedPayments as $payment) {
            $invoice = $payment->invoice;
            $order = $invoice->order;
            $agent = $order->agent;
            
            // Check if this invoice already has a commission
            $existingCommission = Commission::where('invoice_id', $invoice->id)->first();
            
            if (!$existingCommission && $agent) {
                // Calculate commission based on agent's rates
                $fixedAmount = $agent->fixed_commission;
                $percentage = $agent->percentage_commission;
                $totalCommission = Commission::calculateAmount($invoice->total_amount, $fixedAmount, $percentage);
                
                // Determine if commission is paid or pending
                // For simulation, 70% of commissions for paid invoices are also paid
                $isPaid = (rand(1, 10) <= 7);
                $status = $isPaid ? 'paid' : 'pending';
                $paidAt = $isPaid ? Carbon::parse($payment->paid_at)->addDays(rand(1, 14)) : null;
                
                // Create the commission record
                Commission::create([
                    'agent_id' => $agent->id,
                    'invoice_id' => $invoice->id,
                    'fixed_amount' => $fixedAmount,
                    'percentage' => $percentage,
                    'total_commission' => $totalCommission,
                    'status' => $status,
                    'paid_at' => $paidAt,
                    'notes' => $this->getRandomNotes($status),
                ]);
            }
        }
    }
    
    /**
     * Get random notes for a commission record.
     *
     * @param string $status
     * @return string|null
     */
    private function getRandomNotes(string $status): ?string
    {
        if ($status === 'paid') {
            $notes = [
                'Commission paid via bank transfer.',
                'Paid on monthly commission cycle.',
                'Paid alongside other commissions.',
                null,
            ];
        } else {
            $notes = [
                'To be paid in next commission cycle.',
                'Pending verification.',
                'Will be processed at month end.',
                null,
            ];
        }
        
        return $notes[array_rand($notes)];
    }
}

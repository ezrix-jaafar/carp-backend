<?php

namespace Database\Seeders;

use App\Models\Carpet;
use App\Models\Invoice;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get orders that are in later stages of processing or completed and don't have invoices yet
        $orders = Order::whereIn('status', ['cleaned', 'delivered', 'completed'])
            ->whereDoesntHave('invoice')
            ->get();
        
        if ($orders->isEmpty()) {
            $this->command->info('No eligible orders found for invoices. Please run the OrderSeeder first.');
            return;
        }
        
        // Create invoices for each eligible order
        foreach ($orders as $order) {
            // Calculate the total amount based on carpets and any additional charges
            $carpets = Carpet::where('order_id', $order->id)->get();
            $totalAmount = $this->calculateTotalAmount($carpets);
            
            // If no carpets were found, use a default amount
            if ($totalAmount === 0) {
                $totalAmount = rand(100, 500);
            }
            
            // Determine the invoice status based on order status
            $status = $order->status === 'completed' ? 'paid' : 'pending';
            
            // Set up the invoice dates
            $issuedAt = Carbon::parse($order->pickup_date)->addDays(1);
            $dueDate = Carbon::parse($issuedAt)->addDays(14);
            
            // Create a unique invoice number with current date and counter
            $prefix = 'INV';
            $date = now()->format('Ymd');
            
            // Get the highest sequence number used for today's invoices
            $latestInvoice = Invoice::where('invoice_number', 'like', "{$prefix}-{$date}-%")
                ->orderBy('invoice_number', 'desc')
                ->first();
            
            $sequence = 1;
            if ($latestInvoice && preg_match('/-\d{8}-(\d{3})$/', $latestInvoice->invoice_number, $matches)) {
                $sequence = (int)$matches[1] + 1;
            }
            
            $invoiceNumber = "{$prefix}-{$date}-" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
            
            // Create the invoice
            Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totalAmount,
                'status' => $status,
                'issued_at' => $issuedAt,
                'due_date' => $dueDate,
                'notes' => $this->getRandomNotes($status),
            ]);
            
            $this->command->info("Created invoice {$invoiceNumber} for order {$order->reference_number}");
        }
    }
    
    /**
     * Calculate the total amount for an invoice based on carpets.
     *
     * @param \Illuminate\Database\Eloquent\Collection $carpets
     * @return float
     */
    private function calculateTotalAmount($carpets): float
    {
        $totalAmount = 0;
        
        foreach ($carpets as $carpet) {
            // Use the new carpet pricing system
            // This automatically handles carpet_type pricing (fixed or per sq ft)
            // and also includes any additional_charges and addon services
            $carpetPrice = $carpet->calculatePrice();
            $totalAmount += $carpetPrice;
        }
        
        return round($totalAmount, 2);
    }
    
    /**
     * Get random notes for an invoice.
     *
     * @param string $status
     * @return string|null
     */
    private function getRandomNotes(string $status): ?string
    {
        if ($status === 'paid') {
            $notes = [
                'Invoice paid in full.',
                'Payment received. Thank you for your business.',
                'Paid on time.',
                null,
            ];
        } else {
            $notes = [
                'Payment due upon delivery.',
                'Please pay within 14 days.',
                'Accepting bank transfer or credit card.',
                'Please quote invoice number when making payment.',
                null,
            ];
        }
        
        return $notes[array_rand($notes)];
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceRegenerationService
{
    protected $invoiceCalculator;
    
    public function __construct(InvoiceCalculatorService $invoiceCalculator)
    {
        $this->invoiceCalculator = $invoiceCalculator;
    }
    
    /**
     * Regenerate an invoice for an order
     * 
     * @param Order $order
     * @param Invoice $oldInvoice
     * @param array $data
     * @return Invoice
     */
    public function regenerateInvoice(Order $order, Invoice $oldInvoice, array $data): Invoice
    {
        // Begin transaction
        return DB::transaction(function () use ($order, $oldInvoice, $data) {
            // 1. Cancel the old invoice
            $oldInvoice->update([
                'status' => 'canceled',
                'notes' => $oldInvoice->notes . "\n[System] This invoice was canceled and replaced by a new invoice due to changes in the order."
            ]);
            
            // 2. Create new invoice with reference to old invoice
            $newInvoiceData = $data;
            $newInvoiceData['previous_invoice_id'] = $oldInvoice->id;
            
            // Generate new invoice with same number + suffix for audit trail
            $newInvoice = $this->invoiceCalculator->generateInvoice($order, $newInvoiceData);
            
            // Update the invoice number to reflect it's a revision
            $revisionNumber = $this->getNextRevisionNumber($oldInvoice->invoice_number);
            $newInvoice->invoice_number = $revisionNumber;
            $newInvoice->notes = ($newInvoice->notes ? $newInvoice->notes . "\n" : '') . 
                                 "[System] This invoice replaces invoice #{$oldInvoice->invoice_number} due to changes in the order. Original date: {$oldInvoice->issued_at->format('Y-m-d')}.";
            $newInvoice->save();
            
            // The line items should already be created by the invoice calculator service
            
            return $newInvoice;
        });
    }
    
    /**
     * Get the next revision number for an invoice
     * 
     * @param string $invoiceNumber
     * @return string
     */
    protected function getNextRevisionNumber(string $invoiceNumber): string
    {
        // Check if the invoice number already has a revision suffix
        if (preg_match('/(.*)-R(\d+)$/', $invoiceNumber, $matches)) {
            // Increment the revision number
            $baseNumber = $matches[1];
            $revisionNumber = (int)$matches[2] + 1;
            return $baseNumber . '-R' . $revisionNumber;
        }
        
        // First revision
        return $invoiceNumber . '-R1';
    }
}

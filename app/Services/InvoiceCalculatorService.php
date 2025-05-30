<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TaxSetting;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceCalculatorService
{
    /**
     * Calculate the subtotal for an order based on carpets and addon services
     *
     * @param Order $order
     * @return float
     */
    public function calculateSubtotal(Order $order): float
    {
        $subtotal = 0;

        // Calculate for each carpet that is not canceled
        foreach ($order->carpets as $carpet) {
            // Skip carpets with canceled status
            if ($carpet->status === 'canceled') {
                continue;
            }
            $carpetSubtotal = 0;
            
            // Basic carpet price based on carpet type
            if ($carpet->carpetType) {
                if ($carpet->carpetType->is_per_square_foot && $carpet->width && $carpet->length) {
                    $squareFeet = $carpet->width * $carpet->length;
                    $carpetSubtotal += $squareFeet * $carpet->carpetType->price;
                } else {
                    $carpetSubtotal += $carpet->carpetType->price;
                }
            }
            
            // Add pricing for addon services
            foreach ($carpet->addonServices as $addonService) {
                if ($addonService->is_per_square_foot && $carpet->width && $carpet->length) {
                    $squareFeet = $carpet->width * $carpet->length;
                    $carpetSubtotal += $squareFeet * $addonService->price;
                } else {
                    $carpetSubtotal += $addonService->price;
                }
            }
            
            // Add any additional charges specific to this carpet
            $carpetSubtotal += $carpet->additional_charges;
            
            $subtotal += $carpetSubtotal;
        }
        
        return round($subtotal, 2);
    }
    
    /**
     * Calculate the discount amount based on subtotal and discount type/value
     *
     * @param float $subtotal
     * @param float $discountValue
     * @param string $discountType
     * @return float
     */
    public function calculateDiscount(float $subtotal, float $discountValue, string $discountType): float
    {
        if ($discountType === 'percentage') {
            return round($subtotal * ($discountValue / 100), 2);
        }
        
        // Fixed amount discount
        return min($discountValue, $subtotal); // Don't allow discount to exceed subtotal
    }
    
    /**
     * Calculate tax amount based on the subtotal after discount
     *
     * @param float $subtotalAfterDiscount
     * @param TaxSetting|null $taxSetting
     * @return float
     */
    public function calculateTax(float $subtotalAfterDiscount, ?TaxSetting $taxSetting): float
    {
        if (!$taxSetting || !$taxSetting->is_active) {
            return 0;
        }
        
        return $taxSetting->calculateTax($subtotalAfterDiscount);
    }
    
    /**
     * Generate an invoice for an order
     *
     * @param Order $order
     * @param array $data
     * @return Invoice
     */
    public function generateInvoice(Order $order, array $data): Invoice
    {
        // Extract parameters
        $discountValue = $data['discount'] ?? 0;
        $discountType = $data['discount_type'] ?? 'fixed';
        $taxSettingId = $data['tax_setting_id'] ?? null;
        $notes = $data['notes'] ?? null;
        $dueDate = $data['due_date'] ?? now()->addDays(14);
        
        // Get tax setting if provided
        $taxSetting = null;
        if ($taxSettingId) {
            $taxSetting = TaxSetting::find($taxSettingId);
        }
        
        // Calculate invoice amounts
        $subtotal = $this->calculateSubtotal($order);
        $discountAmount = $this->calculateDiscount($subtotal, $discountValue, $discountType);
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        $taxAmount = $this->calculateTax($subtotalAfterDiscount, $taxSetting);
        $totalAmount = $subtotalAfterDiscount + $taxAmount;
        
        // Create the invoice with line items
        return DB::transaction(function () use ($order, $subtotal, $discountAmount, $discountType, $taxSetting, $taxAmount, $totalAmount, $notes, $dueDate) {
            // Create the main invoice
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'discount_type' => $discountType,
                'tax_amount' => $taxAmount,
                'tax_setting_id' => $taxSetting ? $taxSetting->id : null,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'issued_at' => now(),
                'due_date' => $dueDate,
                'notes' => $notes,
            ]);
            
            // Generate line items for each carpet
            $sortOrder = 1;
            foreach ($order->carpets as $carpet) {
                // Handle carpets with canceled status differently but still show them
                $isCanceled = ($carpet->status === 'canceled');
                
                // 1. Add base carpet charge
                if ($carpet->carpetType) {
                    $carpetName = $carpet->carpetType->name;
                    $squareFeet = $carpet->square_footage;
                    $description = "QR: {$carpet->qr_code}";
                    
                    if ($carpet->width && $carpet->length) {
                        $description .= " - {$carpet->width} ft × {$carpet->length} ft ({$squareFeet} sq ft)";
                    }
                    
                    if ($carpet->color) {
                        $description .= " - {$carpet->color}";
                    }
                    
                    // Add CANCELED remark for canceled carpets
                    if ($isCanceled) {
                        $description .= " - [CANCELED]";
                    }
                    
                    // Determine pricing based on carpet type and canceled status
                    if ($isCanceled) {
                        // Canceled carpets show with zero price
                        $invoice->items()->create([
                            'carpet_id' => $carpet->id,
                            'item_type' => 'carpet_base',
                            'name' => $carpetName,
                            'description' => $description,
                            'quantity' => $squareFeet ?: 1,
                            'unit' => $squareFeet ? 'sq_ft' : 'piece',
                            'unit_price' => 0.00, // Zero price for canceled carpets
                            'subtotal' => 0.00,   // Zero subtotal for canceled carpets
                            'sort_order' => $sortOrder++,
                        ]);
                    } else if ($carpet->carpetType->is_per_square_foot && $squareFeet) {
                        // Per square foot pricing (for non-canceled carpets)
                        $invoice->items()->create([
                            'carpet_id' => $carpet->id,
                            'item_type' => 'carpet_base',
                            'name' => $carpetName,
                            'description' => $description,
                            'quantity' => $squareFeet,
                            'unit' => 'sq_ft',
                            'unit_price' => $carpet->carpetType->price,
                            'subtotal' => $squareFeet * $carpet->carpetType->price,
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        // Fixed price per carpet (for non-canceled carpets)
                        $invoice->items()->create([
                            'carpet_id' => $carpet->id,
                            'item_type' => 'carpet_base',
                            'name' => $carpetName,
                            'description' => $description,
                            'quantity' => 1,
                            'unit' => 'piece',
                            'unit_price' => $carpet->carpetType->price,
                            'subtotal' => $carpet->carpetType->price,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
                
                // 2. Add addon services as separate line items
                foreach ($carpet->addonServices as $addonService) {
                    $addonName = $addonService->name;
                    $addonDescription = "Additional service for carpet {$carpet->qr_code}";
                    
                    // Add CANCELED marking for addon services on canceled carpets
                    if ($isCanceled) {
                        $addonDescription .= " - [CANCELED]";
                        
                        // Zero-priced addon for canceled carpet
                        $invoice->items()->create([
                            'carpet_id' => $carpet->id,
                            'item_type' => 'addon_service',
                            'name' => $addonName,
                            'description' => $addonDescription,
                            'quantity' => $carpet->square_footage ?: 1,
                            'unit' => $carpet->square_footage ? 'sq_ft' : 'service',
                            'unit_price' => 0.00,
                            'subtotal' => 0.00,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                    // Only calculate prices for non-canceled carpets
                    else if ($addonService->is_per_square_foot && $carpet->square_footage) {
                        // Per square foot addon pricing
                        $invoice->items()->create([
                            'carpet_id' => $carpet->id,
                            'item_type' => 'addon_service',
                            'name' => $addonName,
                            'description' => $addonDescription,
                            'quantity' => $carpet->square_footage,
                            'unit' => 'sq_ft',
                            'unit_price' => $addonService->price,
                            'subtotal' => $carpet->square_footage * $addonService->price,
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        // Fixed price addon
                        $invoice->items()->create([
                            'carpet_id' => $carpet->id,
                            'item_type' => 'addon_service',
                            'name' => $addonName,
                            'description' => $addonDescription,
                            'quantity' => 1,
                            'unit' => 'service',
                            'unit_price' => $addonService->price,
                            'subtotal' => $addonService->price,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
                
                // 3. Add any additional charges specific to this carpet
                if ($carpet->additional_charges > 0) {
                    $additionalDescription = "Extra charges for carpet {$carpet->qr_code}";
                    
                    // Add CANCELED marking for additional charges on canceled carpets
                    if ($isCanceled) {
                        $additionalDescription .= " - [CANCELED]";
                        
                        // Zero-priced additional charges for canceled carpet
                        $invoice->items()->create([
                            'carpet_id' => $carpet->id,
                            'item_type' => 'other',
                            'name' => 'Additional Charges',
                            'description' => $additionalDescription,
                            'quantity' => 1,
                            'unit' => 'charge',
                            'unit_price' => 0.00,
                            'subtotal' => 0.00,
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        // Regular additional charges for active carpets
                        $invoice->items()->create([
                            'carpet_id' => $carpet->id,
                            'item_type' => 'other',
                            'name' => 'Additional Charges',
                            'description' => $additionalDescription,
                            'quantity' => 1,
                            'unit' => 'charge',
                            'unit_price' => $carpet->additional_charges,
                            'subtotal' => $carpet->additional_charges,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
            }
            
            // Update order status if needed
            if ($order->status === 'cleaned') {
                $order->update(['status' => 'invoiced']);
            }
            
            // Calculate commission if an agent is assigned
            if ($order->agent) {
                $invoice->calculateCommission();
            }
            
            return $invoice;
        });
    }
}

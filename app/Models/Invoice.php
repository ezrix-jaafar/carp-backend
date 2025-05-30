<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'invoice_number',
        'subtotal',
        'discount',
        'discount_type',
        'tax_amount',
        'tax_setting_id',
        'total_amount',
        'status',
        'issued_at',
        'due_date',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'due_date' => 'date',
    ];
    
    /**
     * Get the order that owns the invoice.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the tax setting used for this invoice.
     */
    public function taxSetting(): BelongsTo
    {
        return $this->belongsTo(TaxSetting::class);
    }
    
    /**
     * Get the payments for the invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    
    /**
     * Get the commissions for the invoice.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }
    
    /**
     * Get the line items for the invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }
    
    /**
     * Generate a unique invoice number.
     *
     * @return string
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastInvoice = self::whereDate('created_at', today())->latest()->first();
        
        $sequence = 1;
        if ($lastInvoice && preg_match('/\d+$/', $lastInvoice->invoice_number, $matches)) {
            $sequence = intval($matches[0]) + 1;
        }
        
        return $prefix . '-' . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Calculate the commission for this invoice.
     * 
     * @return array|null
     */
    public function calculateCommission(): ?array
    {
        $agent = $this->order?->agent;
        
        if (!$agent) {
            return null;
        }
        
        // Agent::calculateCommission returns [amount, commissionType], so extract just the amount
        $result = $agent->calculateCommission($this->total_amount);
        
        // Return the full result
        return $result;
    }
}

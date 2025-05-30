<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'amount',
        'status',
        'payment_method',
        'transaction_reference',
        'bill_code',
        'payment_details',
        'paid_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'json',
        'paid_at' => 'datetime',
    ];
    
    /**
     * Get the invoice that owns the payment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
    
    /**
     * Get the ToyyibPay payment URL for this payment.
     * Uses environment switching between sandbox and production.
     *
     * @return string|null
     */
    public function getPaymentUrl(): ?string
    {
        if (!$this->bill_code) {
            return null;
        }
        
        $env = config('services.toyyibpay.env', 'sandbox');
        $baseUrl = config("services.toyyibpay.{$env}.payment_url", 'https://dev.toyyibpay.com/');
        
        return rtrim($baseUrl, '/') . '/' . $this->bill_code;
    }
    
    /**
     * Check payment status from ToyyibPay.
     * This method would be implemented in a service class in a real application.
     *
     * @return bool
     */
    public function checkStatus(): bool
    {
        // In a real implementation, this would call the ToyyibPay API
        // to check the payment status using bill_code
        
        return $this->status === 'completed';
    }
}

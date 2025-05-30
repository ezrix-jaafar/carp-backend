<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommissionType extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'fixed_amount',
        'percentage_rate',
        'is_active',
        'min_invoice_amount',
        'max_invoice_amount',
        'is_default',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fixed_amount' => 'decimal:2',
        'percentage_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'min_invoice_amount' => 'integer',
        'max_invoice_amount' => 'integer',
        'is_default' => 'boolean',
    ];
    
    /**
     * The agents that belong to this commission type.
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_commission_type')
            ->withPivot('fixed_amount_override', 'percentage_rate_override', 'is_active', 'notes')
            ->withTimestamps();
    }
    
    /**
     * Get the commissions for this commission type.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }
    
    /**
     * Check if this commission type is applicable for a given invoice amount.
     *
     * @param float $invoiceAmount
     * @return bool
     */
    public function isApplicableForAmount(float $invoiceAmount): bool
    {
        // If no min or max set, it's applicable
        if ($this->min_invoice_amount === null && $this->max_invoice_amount === null) {
            return true;
        }
        
        // Check min amount if set
        if ($this->min_invoice_amount !== null && $invoiceAmount < $this->min_invoice_amount) {
            return false;
        }
        
        // Check max amount if set
        if ($this->max_invoice_amount !== null && $invoiceAmount > $this->max_invoice_amount) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculate commission amount based on invoice amount.
     *
     * @param float $invoiceAmount
     * @param float|null $fixedAmountOverride
     * @param float|null $percentageRateOverride
     * @return float
     */
    public function calculateCommission(
        float $invoiceAmount, 
        ?float $fixedAmountOverride = null, 
        ?float $percentageRateOverride = null
    ): float {
        // Use overrides if provided, otherwise use the default values
        $fixedAmount = $fixedAmountOverride ?? $this->fixed_amount;
        $percentageRate = $percentageRateOverride ?? $this->percentage_rate;
        
        // Calculate the commission
        $percentageAmount = ($invoiceAmount * $percentageRate) / 100;
        
        return round($fixedAmount + $percentageAmount, 2);
    }
}

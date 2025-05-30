<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Agent extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'fixed_commission',
        'percentage_commission',
        'status',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fixed_commission' => 'decimal:2',
        'percentage_commission' => 'decimal:2',
    ];
    
    /**
     * Get the user that owns the agent.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the orders for the agent.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Get the commissions for the agent.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }
    
    /**
     * Get the commission types for this agent.
     */
    public function commissionTypes(): BelongsToMany
    {
        return $this->belongsToMany(CommissionType::class, 'agent_commission_type')
            ->withPivot('fixed_amount_override', 'percentage_rate_override', 'is_active', 'notes')
            ->withTimestamps();
    }
    
    /**
     * Get the default commission type for this agent if assigned, or the system default.
     *
     * @return CommissionType|null
     */
    public function getDefaultCommissionType(): ?CommissionType
    {
        // First look for assigned commission types that are active
        $activeCommissionTypes = $this->commissionTypes()->wherePivot('is_active', true)->get();
        
        if ($activeCommissionTypes->isNotEmpty()) {
            // If any is set as default in the system, use that first
            $systemDefault = $activeCommissionTypes->firstWhere('is_default', true);
            if ($systemDefault) {
                return $systemDefault;
            }
            
            // Otherwise, just use the first active one
            return $activeCommissionTypes->first();
        }
        
        // If agent has no commission types, use system default
        return CommissionType::where('is_default', true)->where('is_active', true)->first();
    }
    
    /**
     * Find all applicable commission types for a specific invoice amount.
     *
     * @param float $invoiceAmount
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getApplicableCommissionTypes(float $invoiceAmount)
    {
        // Get all active commission types for this agent
        $activeCommissionTypes = $this->commissionTypes()
            ->wherePivot('is_active', true)
            ->where('commission_types.is_active', true)
            ->get();
        
        // Filter to only those applicable for the invoice amount
        return $activeCommissionTypes->filter(function ($commissionType) use ($invoiceAmount) {
            return $commissionType->isApplicableForAmount($invoiceAmount);
        });
    }
    
    /**
     * Calculate commission for a given invoice amount.
     * In the new system, this will identify the best commission type to use.
     *
     * @param float $invoiceAmount
     * @return array Returns [commission amount, commission type used]
     */
    public function calculateCommission(float $invoiceAmount): array
    {
        // Get applicable commission types
        $applicableTypes = $this->getApplicableCommissionTypes($invoiceAmount);
        
        if ($applicableTypes->isEmpty()) {
            // Fall back to default commission type if no applicable ones found
            $commissionType = $this->getDefaultCommissionType();
            
            if (!$commissionType) {
                // Legacy fallback to agent's direct commission values if no commission types exist
                $amount = $this->fixed_commission + ($this->percentage_commission / 100 * $invoiceAmount);
                return [round($amount, 2), null];
            }
        } else {
            // Find the commission type that gives the highest commission
            $commissionType = $applicableTypes->first();
            $highestAmount = $commissionType->calculateCommission(
                $invoiceAmount,
                $commissionType->pivot->fixed_amount_override,
                $commissionType->pivot->percentage_rate_override
            );
            
            foreach ($applicableTypes as $type) {
                $currentAmount = $type->calculateCommission(
                    $invoiceAmount,
                    $type->pivot->fixed_amount_override,
                    $type->pivot->percentage_rate_override
                );
                
                if ($currentAmount > $highestAmount) {
                    $highestAmount = $currentAmount;
                    $commissionType = $type;
                }
            }
        }
        
        // Calculate commission with any override values from pivot table
        $amount = $commissionType->calculateCommission(
            $invoiceAmount,
            $commissionType->pivot->fixed_amount_override ?? null,
            $commissionType->pivot->percentage_rate_override ?? null
        );
        
        return [round($amount, 2), $commissionType];
    }
}

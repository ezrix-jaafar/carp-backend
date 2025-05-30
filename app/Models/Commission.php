<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agent_id',
        'invoice_id',
        'commission_type_id',
        'commission_type_name',
        'fixed_amount',
        'percentage',
        'total_commission',
        'status',
        'paid_at',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fixed_amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'paid_at' => 'datetime',
    ];
    
    /**
     * Get the agent that owns the commission.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
    
    /**
     * Get the invoice that the commission is for.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
    
    /**
     * Get the commission type that this commission uses.
     */
    public function commissionType(): BelongsTo
    {
        return $this->belongsTo(CommissionType::class);
    }
    
    /**
     * Calculate the commission amount based on invoice amount and agent rates.
     *
     * @param float $invoiceAmount
     * @param float $fixedCommission
     * @param float $percentageCommission
     * @return float
     */
    public static function calculateAmount(float $invoiceAmount, float $fixedCommission, float $percentageCommission): float
    {
        $percentageAmount = ($invoiceAmount * $percentageCommission) / 100;
        return $fixedCommission + $percentageAmount;
    }
    
    /**
     * Create a commission record for an agent based on an invoice.
     *
     * @param Agent $agent
     * @param Invoice $invoice
     * @return self
     */
    public static function createFromInvoice(Agent $agent, Invoice $invoice): self
    {
        // Calculate commission using the new system that selects the best commission type
        [$totalCommission, $commissionType] = $agent->calculateCommission($invoice->total_amount);
        
        // Create the commission record
        $commission = [
            'agent_id' => $agent->id,
            'invoice_id' => $invoice->id,
            'total_commission' => $totalCommission,
            'status' => 'pending',
        ];
        
        // If a commission type was used, save its details
        if ($commissionType) {
            $commission['commission_type_id'] = $commissionType->id;
            $commission['commission_type_name'] = $commissionType->name;
            
            // Store the agent's override values or the commission type's default values
            $fixedAmount = $commissionType->pivot->fixed_amount_override ?? $commissionType->fixed_amount;
            $percentage = $commissionType->pivot->percentage_rate_override ?? $commissionType->percentage_rate;
            
            $commission['fixed_amount'] = $fixedAmount;
            $commission['percentage'] = $percentage;
        } else {
            // Legacy fallback - use the agent's direct commission values
            $commission['fixed_amount'] = $agent->fixed_commission;
            $commission['percentage'] = $agent->percentage_commission;
        }
        
        return self::create($commission);
    }
}

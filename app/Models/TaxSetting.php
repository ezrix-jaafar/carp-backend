<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'type',
        'rate',
        'is_active',
        'description',
    ];
    
    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];
    
    /**
     * Calculate tax amount based on the subtotal
     *
     * @param float $subtotal
     * @return float
     */
    public function calculateTax(float $subtotal): float
    {
        if (!$this->is_active) {
            return 0;
        }
        
        if ($this->type === 'percentage') {
            return round($subtotal * ($this->rate / 100), 2);
        }
        
        // Fixed tax amount
        return $this->rate;
    }
}

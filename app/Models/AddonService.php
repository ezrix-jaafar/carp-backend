<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AddonService extends Model
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
        'is_per_square_foot',
        'price',
        'is_active'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_per_square_foot' => 'boolean',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
    
    /**
     * The carpets that belong to this addon service.
     */
    public function carpets(): BelongsToMany
    {
        return $this->belongsToMany(Carpet::class, 'carpet_addon_service')
            ->withPivot('price_override', 'notes')
            ->withTimestamps();
    }
    
    /**
     * Calculate the price for a specific carpet based on its dimensions.
     *
     * @param Carpet|null $carpet
     * @param float|null $priceOverride
     * @return float
     */
    public function calculatePrice(?Carpet $carpet = null, ?float $priceOverride = null): float
    {
        // If there's a price override, use that
        if ($priceOverride !== null) {
            return $priceOverride;
        }
        
        // If not per square foot or no carpet provided, return the base price
        if (!$this->is_per_square_foot || !$carpet) {
            return $this->price;
        }
        
        // If per square foot, calculate based on carpet dimensions
        $squareFeet = $carpet->square_footage ?? 0;
        
        // If we can't determine square footage, use the base price
        if (!$squareFeet) {
            return $this->price;
        }
        
        // Calculate price based on square footage
        return round($squareFeet * $this->price, 2);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarpetType extends Model
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
        'cleaning_instructions',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_per_square_foot' => 'boolean',
        'price' => 'decimal:2',
    ];
    
    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_per_square_foot' => true, // Default to per square foot pricing
    ];
    
    /**
     * Get the carpets for this carpet type.
     */
    public function carpets(): HasMany
    {
        return $this->hasMany(Carpet::class);
    }
    
    /**
     * Calculate price for the given dimensions
     *
     * @param float|null $width
     * @param float|null $length
     * @return float
     */
    public function calculatePrice(?float $width = null, ?float $length = null): float
    {
        // If this is a flat rate carpet type, not per square foot
        if (!$this->is_per_square_foot) {
            return $this->price;
        }
        
        // If dimensions are not provided, return the base price
        if ($width === null || $length === null) {
            return $this->price;
        }
        
        // Calculate the square feet and multiply by the price per square foot
        $squareFeet = $width * $length;
        return round($squareFeet * $this->price, 2);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Carpet extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'carpet_type_id',
        'qr_code',
        'type', // Keep legacy type field temporarily during migration
        'dimensions', // Keep legacy dimensions field temporarily during migration
        'width',
        'length',
        'color',
        'status',
        'notes',
        'additional_charges',
        'pack_number',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dimensions' => 'json',
        'width' => 'decimal:2',
        'length' => 'decimal:2',
        'additional_charges' => 'decimal:2',
    ];
    
    /**
     * Get the order that owns the carpet.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the images for the carpet.
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }
    
    /**
     * Get the carpet type for this carpet.
     */
    public function carpetType(): BelongsTo
    {
        return $this->belongsTo(CarpetType::class);
    }
    
    /**
     * Get the addon services for this carpet.
     */
    public function addonServices(): BelongsToMany
    {
        return $this->belongsToMany(AddonService::class, 'carpet_addon_service')
            ->withPivot('price_override', 'notes')
            ->withTimestamps();
    }
    
    /**
     * Calculate the price of this carpet based on its type, dimensions, and addon services.
     * 
     * @param bool $includeAddons Whether to include addon services in the price calculation
     * @return float
     */
    public function calculatePrice(bool $includeAddons = true): float
    {
        if (!$this->carpetType) {
            return 0;
        }
        
        // Base price from carpet type
        $basePrice = $this->carpetType->calculatePrice($this->width, $this->length);
        
        // Add additional charges
        $totalPrice = $basePrice + ($this->additional_charges ?? 0);
        
        // Add addon services if requested
        if ($includeAddons) {
            // Get all addon services with their pivot data
            $addonServices = $this->addonServices;
            
            foreach ($addonServices as $addon) {
                // Calculate addon price (using override if available)
                $priceOverride = $addon->pivot->price_override;
                $addonPrice = $addon->calculatePrice($this, $priceOverride);
                
                // Add to total
                $totalPrice += $addonPrice;
            }
        }
        
        return round($totalPrice, 2);
    }
    
    /**
     * Calculate the square footage of the carpet.
     * 
     * @return float|null
     */
    public function getSquareFootageAttribute(): ?float
    {
        if ($this->width && $this->length) {
            return round($this->width * $this->length, 2);
        }
        
        // Legacy support for dimensions field during migration
        if (!$this->width && !$this->length && isset($this->dimensions['width']) && isset($this->dimensions['length'])) {
            return round((float)$this->dimensions['width'] * (float)$this->dimensions['length'], 2);
        }
        
        return null;
    }
    
    /**
     * Generate a unique QR code for a carpet.
     *
     * @param int $orderId
     * @param int $sequence
     * @return string
     */
    /**
     * Generate a unique QR / carpet number in the format CARP-YYMMXXXXX.
     * YY  = two-digit year
     * MM  = two-digit month
     * XXXXX = 5-digit running number that resets each month
     */
    public static function generateQrCode(int $orderId = 0, int $sequence = 0): string
    {
        $yearMonth = now()->format('ym'); // e.g. 2506 for June 2025
        $basePrefix = 'CARP-' . $yearMonth;

        // Find latest carpet this month
        $latest = self::where('qr_code', 'like', $basePrefix . '%')
            ->orderByDesc('qr_code')
            ->first();

        if ($latest) {
            // Extract numeric part (last 5 chars) and increment
            $lastNumber = intval(substr($latest->qr_code, -5));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $sequencePart = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        return $basePrefix . $sequencePart; // e.g. CARP-250600001
    }
}

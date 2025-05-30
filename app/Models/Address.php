<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'label',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'is_default',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];
    
    /**
     * Get the client that owns the address.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    /**
     * Get the orders that use this address for pickup.
     */
    public function pickupOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'pickup_address_id');
    }
    
    /**
     * Get the orders that use this address for delivery.
     */
    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_address_id');
    }
    
    /**
     * Set this address as the default for its client.
     */
    public function setAsDefault(): void
    {
        // First, unset default flag for all other addresses of this client
        if ($this->client_id) {
            self::where('client_id', $this->client_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);
        }
        
        // Set this address as default
        $this->is_default = true;
        $this->save();
    }
    
    /**
     * Get the full formatted address.
     */
    public function getFormattedAttribute(): string
    {
        $parts = [
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code
        ];
        
        return implode(', ', array_filter($parts));
    }
}

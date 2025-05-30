<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'phone',
        'address', // Legacy field - will be removed after address book migration
        'city',    // Legacy field - will be removed after address book migration
        'state',   // Legacy field - will be removed after address book migration
    ];
    
    /**
     * Get the user that owns the client.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the orders for the client.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Get the addresses for the client.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }
    
    /**
     * Get the default address for the client.
     */
    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }
}

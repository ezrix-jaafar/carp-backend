<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\OrderStatusHistory;

class Order extends Model
{
    use HasFactory;
    
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->reference_number)) {
                $order->reference_number = self::generateReferenceNumber();
            }
        });

        // Log status changes
        static::updated(function ($order) {
            if ($order->wasChanged('status')) {
                OrderStatusHistory::create([
                    'order_id'   => $order->id,
                    'old_status' => $order->getOriginal('status'),
                    'new_status' => $order->status,
                    'changed_by' => Auth::id(),
                ]);
            }
        });
    }
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'agent_id',
        'status',
        'pickup_date',
        'pickup_address_id',   // Field referencing addresses table
        'delivery_date',
        'delivery_address_id', // Field referencing addresses table
        'notes',
        'total_carpets',
        'reference_number',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $appends = ['status_label'];

    protected $casts = [
        'pickup_date' => 'date',
        'delivery_date' => 'date',
    ];
    
    /**
     * Get the client that owns the order.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    /**
     * Get the agent that handles the order.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
    
    /**
     * Get the carpets for the order.
     */
    public function carpets(): HasMany
    {
        return $this->hasMany(Carpet::class);
    }
    
    /**
     * Get the invoice for the order.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
    
    /**
     * Get the pickup address for the order.
     */
    public function pickupAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'pickup_address_id');
    }
    
    /**
     * Get the delivery address for the order.
     */
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'delivery_address_id');
    }

    /**
     * Get the status history records for the order.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }


    /**
     * Generate a unique reference number.
     *
     * @return string
     */
    /**
     * Get a human-readable label for the current status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'assigned'   => 'Waiting for Pickup',
            'delivered'  => 'Delivered To Agent',
            default      => Str::of($this->status)->replace('_', ' ')->title(),
        };
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $lastOrder = self::whereDate('created_at', today())->latest()->first();
        
        $sequence = 1;
        if ($lastOrder && preg_match('/\d+$/', $lastOrder->reference_number, $matches)) {
            $sequence = intval($matches[0]) + 1;
        }
        
        return $prefix . '-' . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}

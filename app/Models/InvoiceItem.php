<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'carpet_id',
        'item_type',
        'name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'sort_order',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];
    
    /**
     * Get the invoice that owns the invoice item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
    
    /**
     * Get the carpet associated with this invoice item (if any).
     */
    public function carpet(): BelongsTo
    {
        return $this->belongsTo(Carpet::class);
    }
}

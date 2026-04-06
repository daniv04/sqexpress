<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking',
        'user_id',
        'shipping_method_id',
        'description',
        'weight',
        'approx_value',
        'status',
        'shelf_location',
        'prealerted_at',
        'service_cost',
        'discount_amount',
        'delivery_fee',
        'invoice_number',
        'invoice_generated_at',
        'points_earned',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'approx_value' => 'decimal:2',
            'service_cost' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'prealerted_at' => 'datetime',
            'invoice_generated_at' => 'datetime',
            'points_earned' => 'integer',
        ];
    }

    public function hasInvoice(): bool
    {
        return $this->invoice_number !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PackageStatusHistory::class);
    }
}

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
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'approx_value' => 'decimal:2',
            'prealerted_at' => 'datetime',
        ];
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

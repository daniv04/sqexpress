<?php

namespace App\Models;

use App\Enums\PackageStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'created_by',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'total',
        'exchange_rate',
        'total_crc',
        'points_earned',
        'generated_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            // stored in CRC (colones), not USD
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'exchange_rate' => 'decimal:2',
            'total_crc' => 'decimal:2',
            'points_earned' => 'integer',
            'generated_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isFullyDelivered(): bool
    {
        return $this->packages->isNotEmpty()
            && $this->packages->every(fn (Package $package): bool => $package->status === PackageStatus::DELIVERED->value);
    }

    public function scopeFullyDelivered(Builder $query): Builder
    {
        return $query->whereDoesntHave('packages', fn ($q) => $q->where('status', '!=', PackageStatus::DELIVERED->value));
    }

    public function scopeNotFullyDelivered(Builder $query): Builder
    {
        return $query->whereHas('packages', fn ($q) => $q->where('status', '!=', PackageStatus::DELIVERED->value));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}

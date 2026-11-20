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
        'points_redeemed',
        'points_discount_crc',
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
            'points_redeemed' => 'integer',
            'points_discount_crc' => 'decimal:2',
            'generated_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * Fully delivered once every package is delivered or canceled (both
     * final states) and at least one was actually delivered.
     */
    public function isFullyDelivered(): bool
    {
        return $this->packages->contains('status', PackageStatus::DELIVERED->value)
            && $this->packages->every(fn (Package $package): bool => in_array($package->status, self::closedPackageStatuses(), true));
    }

    /**
     * @return list<string>
     */
    private static function closedPackageStatuses(): array
    {
        return [PackageStatus::DELIVERED->value, PackageStatus::CANCELED->value];
    }

    public function canAddPackages(): bool
    {
        return ! $this->isPaid() && ! $this->isFullyDelivered();
    }

    /**
     * Invoices without packages keep their previous tab ("delivered").
     */
    public function scopeFullyDelivered(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('packages', fn ($q) => $q->whereNotIn('status', self::closedPackageStatuses()))
            ->where(fn ($q) => $q
                ->whereHas('packages', fn ($p) => $p->where('status', PackageStatus::DELIVERED->value))
                ->orWhereDoesntHave('packages'));
    }

    public function scopeNotFullyDelivered(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q
            ->whereHas('packages', fn ($p) => $p->whereNotIn('status', self::closedPackageStatuses()))
            ->orWhere(fn ($q) => $q
                ->whereHas('packages')
                ->whereDoesntHave('packages', fn ($p) => $p->where('status', PackageStatus::DELIVERED->value))));
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

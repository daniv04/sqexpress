<?php

namespace App\Enums;

enum PackageStatus: string
{
    case PREALERTED = 'prealerted';
    case RECEIVED_IN_WAREHOUSE = 'received_in_warehouse';
    case ASSIGNED_FLIGHT = 'assigned_flight';
    case RECEIVED_IN_CUSTOMS = 'received_in_customs';
    case RECEIVED_IN_BUSINESS = 'received_in_business';
    case READY_TO_DELIVER = 'ready_to_deliver';
    case DELIVERED = 'delivered';
    case CANCELED = 'canceled';

    public function nextAllowedStatuses(): array
    {
        return match ($this) {
            self::PREALERTED => [self::RECEIVED_IN_WAREHOUSE, self::CANCELED],
            self::RECEIVED_IN_WAREHOUSE => [self::ASSIGNED_FLIGHT, self::CANCELED],
            self::ASSIGNED_FLIGHT => [self::RECEIVED_IN_CUSTOMS, self::CANCELED],
            self::RECEIVED_IN_CUSTOMS => [self::RECEIVED_IN_BUSINESS, self::CANCELED],
            self::RECEIVED_IN_BUSINESS => [self::READY_TO_DELIVER, self::CANCELED],
            self::READY_TO_DELIVER => [self::DELIVERED],
            self::DELIVERED, self::CANCELED => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->nextAllowedStatuses(), true);
    }

    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }
}

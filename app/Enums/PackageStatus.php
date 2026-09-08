<?php

namespace App\Enums;

enum PackageStatus: string
{
    case PREALERTED = 'prealerted';
    case RECEIVED_IN_WAREHOUSE = 'received_in_warehouse';
    case ASSIGNED_FLIGHT = 'assigned_flight';
    case IN_TRANSIT = 'in_transit';
    case RECEIVED_IN_CUSTOMS = 'received_in_customs';
    case CUSTOMS_PROCESS_FINISHED = 'customs_process_finished';
    case PENDING_WAREHOUSE_RECEPTION = 'pending_warehouse_reception';
    case RECEIVED_IN_BUSINESS = 'received_in_business';
    case READY_TO_DELIVER = 'ready_to_deliver';
    case DELIVERED = 'delivered';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PREALERTED            => 'Prealertado',
            self::RECEIVED_IN_WAREHOUSE => 'Recibido en Bodega',
            self::ASSIGNED_FLIGHT       => 'Programado para Envío',
            self::IN_TRANSIT            => 'En Tránsito a CR',
            self::RECEIVED_IN_CUSTOMS   => 'Recibido en Aduana',
            self::CUSTOMS_PROCESS_FINISHED => 'Liberado de Aduana',
            self::PENDING_WAREHOUSE_RECEPTION => 'Pendiente de Ingreso a SQEXPRESS',
            self::RECEIVED_IN_BUSINESS  => 'Recibido en Oficina',
            self::READY_TO_DELIVER      => 'Listo para Entregar',
            self::DELIVERED             => 'Entregado',
            self::CANCELED              => 'Cancelado',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PREALERTED               => 'El cliente registró el paquete, pero todavía no ha llegado a la bodega del país de origen. Es el estado inicial.',
            self::RECEIVED_IN_WAREHOUSE    => 'El paquete llegó físicamente a la bodega del proveedor en el país de origen.',
            self::ASSIGNED_FLIGHT          => 'El paquete fue asignado a un vuelo o envío programado hacia Costa Rica.',
            self::IN_TRANSIT               => 'El paquete está en camino a Costa Rica dentro del vuelo o envío asignado.',
            self::RECEIVED_IN_CUSTOMS      => 'El paquete llegó a Costa Rica y se encuentra en trámite de desalmacenaje en aduana.',
            self::CUSTOMS_PROCESS_FINISHED => 'El paquete fue liberado de aduana y puede trasladarse a la oficina.',
            self::PENDING_WAREHOUSE_RECEPTION => 'Pasó más de un día desde que se liberó de aduana sin confirmarse su llegada física a la oficina. Es automático: avisa al cliente que el paquete sigue en camino, para que no pregunte por qué "no ha llegado".',
            self::RECEIVED_IN_BUSINESS     => 'El paquete llegó físicamente a la oficina en Costa Rica. Desde aquí se le puede asignar el peso real y ya se puede generar la factura.',
            self::READY_TO_DELIVER         => 'El paquete fue revisado y está listo para ser entregado o retirado por el cliente. También se le puede generar factura desde aquí.',
            self::DELIVERED                => 'El paquete fue entregado al cliente. Estado final, no admite más cambios.',
            self::CANCELED                 => 'El paquete fue cancelado (extravío, rechazo, error de registro, etc.). Estado final, no admite más cambios.',
        };
    }

    /**
     * Filament color name, matching PackageResource::statusColor().
     */
    public function color(): string
    {
        return match ($this) {
            self::PREALERTED               => 'gray',
            self::RECEIVED_IN_WAREHOUSE,
            self::ASSIGNED_FLIGHT,
            self::IN_TRANSIT               => 'info',
            self::RECEIVED_IN_CUSTOMS,
            self::CUSTOMS_PROCESS_FINISHED,
            self::PENDING_WAREHOUSE_RECEPTION,
            self::RECEIVED_IN_BUSINESS     => 'warning',
            self::READY_TO_DELIVER,
            self::DELIVERED                => 'success',
            self::CANCELED                 => 'danger',
        };
    }

    public function nextAllowedStatuses(): array
    {
        return match ($this) {
            self::PREALERTED => [self::RECEIVED_IN_WAREHOUSE, self::CANCELED],
            self::RECEIVED_IN_WAREHOUSE => [self::ASSIGNED_FLIGHT, self::CANCELED],
            self::ASSIGNED_FLIGHT => [self::IN_TRANSIT, self::CANCELED],
            self::IN_TRANSIT => [self::RECEIVED_IN_CUSTOMS, self::CANCELED],
            self::RECEIVED_IN_CUSTOMS => [self::CUSTOMS_PROCESS_FINISHED, self::CANCELED],
            // RECEIVED_IN_BUSINESS listed first so automated/API-driven advances
            // (which pick the first non-canceled option) always jump straight
            // there, skipping the pending-reception waypoint entirely.
            self::CUSTOMS_PROCESS_FINISHED => [self::RECEIVED_IN_BUSINESS, self::PENDING_WAREHOUSE_RECEPTION, self::CANCELED],
            self::PENDING_WAREHOUSE_RECEPTION => [self::RECEIVED_IN_BUSINESS, self::CANCELED],
            self::RECEIVED_IN_BUSINESS => [self::READY_TO_DELIVER, self::DELIVERED, self::CANCELED],
            self::READY_TO_DELIVER => [self::DELIVERED],
            self::DELIVERED, self::CANCELED => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->nextAllowedStatuses(), true);
    }

    /**
     * A package can be invoiced once it has physically arrived at the
     * business, without needing the manual "ready to deliver" step first.
     */
    public function isInvoiceable(): bool
    {
        return in_array($this, [
            self::RECEIVED_IN_BUSINESS,
            self::READY_TO_DELIVER,
        ], true);
    }

    /**
     * Weight is assigned manually by an admin once the package
     * has physically arrived at the business.
     */
    public function allowsWeightAssignment(): bool
    {
        return in_array($this, [
            self::RECEIVED_IN_BUSINESS,
            self::READY_TO_DELIVER,
            self::DELIVERED,
        ], true);
    }

    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }

    /**
     * Value => label map, single source of truth for every UI that needs it
     * (admin panel, client dashboard, package list/detail, emails).
     */
    public static function labels(): array
    {
        return array_combine(
            self::values(),
            array_map(static fn (self $status) => $status->label(), self::cases()),
        );
    }
}

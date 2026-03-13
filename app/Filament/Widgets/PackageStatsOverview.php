<?php

namespace App\Filament\Widgets;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PackageStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $counts = Package::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $activeClients = User::where('role', 'user')->count();

        return [
            Stat::make('Prealertados', $counts->get(PackageStatus::PREALERTED->value, 0))
                ->icon('heroicon-o-bell')
                ->color('gray'),

            Stat::make('En bodega', $counts->get(PackageStatus::RECEIVED_IN_WAREHOUSE->value, 0))
                ->icon('heroicon-o-building-storefront')
                ->color('info'),

            Stat::make('Vuelo asignado', $counts->get(PackageStatus::ASSIGNED_FLIGHT->value, 0))
                ->icon('heroicon-o-paper-airplane')
                ->color('info'),

            Stat::make('En aduana CR', $counts->get(PackageStatus::RECEIVED_IN_CUSTOMS->value, 0))
                ->icon('heroicon-o-shield-check')
                ->color('warning'),

            Stat::make('En empresa', $counts->get(PackageStatus::RECEIVED_IN_BUSINESS->value, 0))
                ->icon('heroicon-o-home-modern')
                ->color('warning'),

            Stat::make('Listos para entregar', $counts->get(PackageStatus::READY_TO_DELIVER->value, 0))
                ->icon('heroicon-o-truck')
                ->color('success'),

            Stat::make('Entregados', $counts->get(PackageStatus::DELIVERED->value, 0))
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Cancelados', $counts->get(PackageStatus::CANCELED->value, 0))
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Clientes activos', $activeClients)
                ->icon('heroicon-o-users')
                ->color('primary'),
        ];
    }
}

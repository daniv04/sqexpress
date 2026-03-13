<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PackageResource;
use App\Models\PackageStatusHistory;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPackagesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Últimas actividades de estado';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PackageStatusHistory::query()
                    ->with(['package.user', 'changedBy'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('package.tracking')
                    ->label('Tracking')
                    ->fontFamily('mono')
                    ->url(fn (PackageStatusHistory $record): string => PackageResource::getUrl('view', ['record' => $record->package_id]))
                    ->color('primary'),

                Tables\Columns\TextColumn::make('package.user.locker_code')
                    ->label('Casillero')
                    ->badge()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('package.user.name')
                    ->label('Cliente'),

                Tables\Columns\TextColumn::make('from_status')
                    ->label('De')
                    ->formatStateUsing(fn (?string $state): string => $state ? PackageResource::statusLabel($state) : '—')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? PackageResource::statusColor($state) : 'gray'),

                Tables\Columns\TextColumn::make('to_status')
                    ->label('A')
                    ->formatStateUsing(fn (string $state): string => PackageResource::statusLabel($state))
                    ->badge()
                    ->color(fn (string $state): string => PackageResource::statusColor($state)),

                Tables\Columns\TextColumn::make('changedBy.name')
                    ->label('Por')
                    ->placeholder('Sistema'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}

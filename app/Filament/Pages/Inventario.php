<?php

namespace App\Filament\Pages;

use App\Enums\PackageStatus;
use App\Filament\Resources\PackageResource;
use App\Models\Package;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Inventario extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $title = 'Inventario — Paquetes en empresa';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.inventario';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Package::query()
                    ->where('status', PackageStatus::RECEIVED_IN_BUSINESS->value)
                    ->with(['user', 'shippingMethod'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('tracking')
                    ->label('Tracking')
                    ->searchable()
                    ->fontFamily('mono')
                    ->copyable()
                    ->url(fn (Package $record): string => PackageResource::getUrl('view', ['record' => $record])),

                Tables\Columns\TextColumn::make('user.locker_code')
                    ->label('Casillero')
                    ->searchable()
                    ->badge()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Peso')
                    ->suffix(' lbs')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('shelf_location')
                    ->label('Estante')
                    ->searchable()
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('shippingMethod.name')
                    ->label('Método')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha llegada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->emptyStateIcon('heroicon-o-archive-box')
            ->emptyStateHeading('Sin paquetes en empresa')
            ->emptyStateDescription('No hay paquetes con estado "Recibido en empresa" en este momento.');
    }
}

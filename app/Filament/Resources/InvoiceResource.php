<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Services\DbService\InvoiceService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $slug = 'facturas';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Facturas';
    protected static ?string $modelLabel = 'Factura';
    protected static ?string $pluralModelLabel = 'Facturas';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('N° Factura')->searchable()->sortable()
                    ->badge()->fontFamily('mono')->color('success'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.locker_code')
                    ->label('Casillero')->badge()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('packages_count')
                    ->label('# Paquetes')
                    ->counts('packages')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')->prefix('$')->sortable(),
                Tables\Columns\IconColumn::make('discount_amount')
                    ->label('Descuento')
                    ->icon(fn ($state): string => (float) $state > 0 ? 'heroicon-o-tag' : '')
                    ->color('success')
                    ->tooltip(fn ($state): string => (float) $state > 0 ? "10% cliente nuevo: -\${$state}" : ''),
                Tables\Columns\TextColumn::make('delivery_fee')
                    ->label('Entrega')
                    ->prefix('$')
                    ->sortable()
                    ->visible(fn ($record) => $record && (float) $record->delivery_fee > 0),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')->prefix('$')->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('points_earned')
                    ->label('Puntos')->suffix(' pts'),
                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? 'Cancelada' : 'Pendiente')
                    ->color(fn (?string $state): string => $state !== null ? 'success' : 'warning')
                    ->sortable(),
            ])
            ->defaultSort('generated_at', 'desc')
            ->filters([
                Filter::make('generated_at')
                    ->label('Rango de fechas')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'], fn ($q) => $q->whereDate('generated_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('generated_at', '<=', $data['until']))),

                TernaryFilter::make('paid_at')
                    ->label('Estado de pago')
                    ->placeholder('Todas')
                    ->trueLabel('Canceladas')
                    ->falseLabel('Pendientes')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('paid_at'),
                        false: fn (Builder $q) => $q->whereNull('paid_at'),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('descargar_pdf')
                    ->label('Descargar PDF')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->url(fn (Invoice $record): string => route('admin.invoices.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('toggle_pagada')
                    ->label(fn (Invoice $record): string => $record->isPaid() ? 'Marcar pendiente' : 'Marcar cancelada')
                    ->icon(fn (Invoice $record): string => $record->isPaid() ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Invoice $record): string => $record->isPaid() ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Invoice $record): string => $record->isPaid()
                        ? 'Marcar factura como pendiente'
                        : 'Marcar factura como cancelada')
                    ->modalDescription(fn (Invoice $record): string => $record->isPaid()
                        ? '¿Confirmás que esta factura vuelve a estar pendiente de pago?'
                        : '¿Confirmás que esta factura ya fue pagada?')
                    ->modalSubmitActionLabel('Confirmar')
                    ->action(function (Invoice $record) {
                        $service = app(InvoiceService::class);
                        if ($record->isPaid()) {
                            $service->markAsUnpaid($record);
                        } else {
                            $service->markAsPaid($record);
                        }
                        Notification::make()
                            ->title($record->fresh()->isPaid() ? 'Factura marcada como cancelada' : 'Factura marcada como pendiente')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view'  => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\DbService\InvoiceService;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('descargar_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn (): string => route('admin.invoices.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('reenviar_email')
                ->label('Reenviar Email')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    app(InvoiceService::class)->resendInvoice($this->record);
                    Notification::make()
                        ->title('Email reenviado')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('toggle_pagada')
                ->label(fn (): string => $this->record->isPaid() ? 'Marcar pendiente' : 'Marcar cancelada')
                ->icon(fn (): string => $this->record->isPaid() ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn (): string => $this->record->isPaid() ? 'gray' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->record->isPaid()
                    ? 'Marcar factura como pendiente'
                    : 'Marcar factura como cancelada')
                ->modalDescription(fn (): string => $this->record->isPaid()
                    ? '¿Confirmás que esta factura vuelve a estar pendiente de pago?'
                    : '¿Confirmás que esta factura ya fue pagada?')
                ->modalSubmitActionLabel('Confirmar')
                ->action(function () {
                    $service = app(InvoiceService::class);
                    if ($this->record->isPaid()) {
                        $service->markAsUnpaid($this->record);
                    } else {
                        $service->markAsPaid($this->record);
                    }
                    Notification::make()
                        ->title($this->record->fresh()->isPaid() ? 'Factura marcada como cancelada' : 'Factura marcada como pendiente')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('marcar_entregados')
                ->label('Marcar como entregados')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->visible(fn (): bool => !$this->record->isFullyDelivered())
                ->requiresConfirmation()
                ->modalHeading('Marcar paquetes como entregados')
                ->modalDescription(fn (): string => 'Se marcarán como entregados los ' . $this->record->packages->count() . ' paquete(s) de esta factura y se notificará por correo al cliente.')
                ->modalSubmitActionLabel('Confirmar')
                ->action(function () {
                    app(InvoiceService::class)->markPackagesAsDelivered($this->record, auth()->id());
                    Notification::make()
                        ->title('Paquetes marcados como entregados')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Factura')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('invoice_number')
                        ->label('N° Factura')->fontFamily('mono')->badge()->color('success'),
                    Infolists\Components\TextEntry::make('generated_at')
                        ->label('Fecha de emisión')->dateTime('d/m/Y H:i'),
                    Infolists\Components\TextEntry::make('paid_at')
                        ->label('Estado de pago')
                        ->badge()
                        ->formatStateUsing(fn (?string $state): string => $state !== null ? 'Cancelada' : 'Pendiente')
                        ->color(fn (?string $state): string => $state !== null ? 'success' : 'warning'),
                    Infolists\Components\TextEntry::make('entrega')
                        ->label('Estado de entrega')
                        ->badge()
                        ->state(fn ($record): string => $record->isFullyDelivered() ? 'Entregado' : 'Pendiente')
                        ->color(fn ($record): string => $record->isFullyDelivered() ? 'success' : 'warning'),
                    Infolists\Components\TextEntry::make('subtotal')
                        ->label('Subtotal')->prefix('$'),
                    Infolists\Components\TextEntry::make('discount_amount')
                        ->label('Descuento (10% cliente nuevo)')->prefix('- $')
                        ->color('success')
                        ->visible(fn ($record): bool => (float) $record->discount_amount > 0),
                    Infolists\Components\TextEntry::make('delivery_fee')
                        ->label('Cargo por entrega')->prefix('₡')
                        ->color('warning')
                        ->visible(fn ($record): bool => (float) $record->delivery_fee > 0),
                    Infolists\Components\TextEntry::make('total')
                        ->label('Total')
                        ->prefix('$')
                        ->weight(FontWeight::Bold)
                        ->size(TextEntrySize::Large),
                    Infolists\Components\TextEntry::make('total_crc')
                        ->label('Total a pagar (CRC)')
                        ->prefix('₡')
                        ->weight(FontWeight::Bold)
                        ->size(TextEntrySize::Large)
                        ->visible(fn ($record): bool => $record->total_crc !== null),
                    Infolists\Components\TextEntry::make('points_earned')
                        ->label('Puntos otorgados')->suffix(' pts'),
                ]),

            Infolists\Components\Section::make('Paquetes incluidos')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('packages')
                        ->label('')
                        ->columns(5)
                        ->schema([
                            Infolists\Components\TextEntry::make('tracking')
                                ->label('Tracking')->fontFamily('mono')->copyable(),
                            Infolists\Components\TextEntry::make('description')
                                ->label('Descripción'),
                            Infolists\Components\TextEntry::make('weight')
                                ->label('Peso')->suffix(' kg')->placeholder('—'),
                            Infolists\Components\TextEntry::make('service_cost')
                                ->label('Costo')->prefix('$'),
                            Infolists\Components\TextEntry::make('shippingMethod.name')
                                ->label('Método'),
                        ]),
                ]),

            Infolists\Components\Section::make('Cliente')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('Nombre'),
                    Infolists\Components\TextEntry::make('user.email')->label('Correo'),
                    Infolists\Components\TextEntry::make('user.phone')
                        ->label('Teléfono')->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.locker_code')
                        ->label('Casillero')->badge()->fontFamily('mono'),
                    Infolists\Components\TextEntry::make('ubicacion')
                        ->label('Ubicación')
                        ->state(fn ($record): string => collect([
                            $record->user->provincia?->nombre,
                            $record->user->canton?->nombre,
                            $record->user->distrito?->nombre,
                            $record->user->barrio?->nombre,
                        ])->filter()->implode(', '))
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.address')
                        ->label('Dirección')->placeholder('—'),
                ]),
        ]);
    }
}

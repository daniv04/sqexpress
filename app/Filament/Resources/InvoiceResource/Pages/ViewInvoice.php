<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PackageResource;
use App\Services\DbService\InvoiceService;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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
                    Infolists\Components\TextEntry::make('subtotal')
                        ->label('Subtotal')->prefix('$'),
                    Infolists\Components\TextEntry::make('discount_amount')
                        ->label('Descuento (10% cliente nuevo)')->prefix('- $')
                        ->color('success')
                        ->visible(fn ($record): bool => (float) $record->discount_amount > 0),
                    Infolists\Components\TextEntry::make('delivery_fee')
                        ->label('Cargo por entrega')->prefix('$')
                        ->color('warning')
                        ->visible(fn ($record): bool => (float) $record->delivery_fee > 0),
                    Infolists\Components\TextEntry::make('total')
                        ->label('Total')
                        ->prefix('$')
                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                        ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                    Infolists\Components\TextEntry::make('total_crc')
                        ->label('Total a pagar (CRC)')
                        ->prefix('₡')
                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                        ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large)
                        ->visible(fn ($record): bool => $record->exchange_rate !== null),
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
                        ])->filter()->implode(', '))
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.address')
                        ->label('Dirección')->placeholder('—'),
                ]),
        ]);
    }
}

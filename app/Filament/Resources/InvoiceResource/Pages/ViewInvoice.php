<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PackageResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
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
                    Infolists\Components\TextEntry::make('invoice_generated_at')
                        ->label('Fecha de emisión')->dateTime('d/m/Y H:i'),
                    Infolists\Components\TextEntry::make('service_cost')
                        ->label('Subtotal')->prefix('₡'),
                    Infolists\Components\TextEntry::make('discount_amount')
                        ->label('Descuento (10% cliente nuevo)')->prefix('- ₡')
                        ->color('success')
                        ->visible(fn ($record): bool => (float) $record->discount_amount > 0),
                    Infolists\Components\TextEntry::make('delivery_fee')
                        ->label('Cargo por entrega')->prefix('₡')
                        ->color('warning')
                        ->visible(fn ($record): bool => (float) $record->delivery_fee > 0),
                    Infolists\Components\TextEntry::make('points_earned')
                        ->label('Puntos otorgados')->suffix(' pts'),
                ]),

            Infolists\Components\Section::make('Paquete')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('tracking')
                        ->label('Tracking')->fontFamily('mono')->copyable(),
                    Infolists\Components\TextEntry::make('user.name')->label('Cliente'),
                    Infolists\Components\TextEntry::make('user.locker_code')
                        ->label('Casillero')->badge()->fontFamily('mono'),
                    Infolists\Components\TextEntry::make('description')
                        ->label('Descripción')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('weight')
                        ->label('Peso')->suffix(' lbs')->placeholder('—'),
                    Infolists\Components\TextEntry::make('shippingMethod.name')
                        ->label('Método de envío'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Estado')->badge()
                        ->formatStateUsing(fn (string $state): string => PackageResource::statusLabel($state))
                        ->color(fn (string $state): string => PackageResource::statusColor($state)),
                ]),

            Infolists\Components\Section::make('Cliente')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('user.email')->label('Correo'),
                    Infolists\Components\TextEntry::make('user.phone')
                        ->label('Teléfono')->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.address')
                        ->label('Dirección')->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.distrito.name')
                        ->label('Distrito')->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.canton.name')
                        ->label('Cantón')->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.provincia.name')
                        ->label('Provincia')->placeholder('—'),
                ]),
        ]);
    }
}

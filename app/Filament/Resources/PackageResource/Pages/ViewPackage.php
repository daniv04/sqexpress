<?php

namespace App\Filament\Resources\PackageResource\Pages;

use App\Enums\PackageStatus;
use App\Filament\Resources\PackageResource;
use App\Services\DbService\InvoiceService;
use App\Services\DbService\PackageService;
use DomainException;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPackage extends ViewRecord
{
    protected static string $resource = PackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cambiar_estado')
                ->label('Cambiar estado')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form(fn (): array => [
                    Forms\Components\Select::make('new_status')
                        ->label('Nuevo estado')
                        ->options(fn () => $this->nextStatusOptions())
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('shelf_location')
                        ->label('Estante (obligatorio)')
                        ->visible(fn (Forms\Get $get): bool => $get('new_status') === PackageStatus::RECEIVED_IN_BUSINESS->value
                        )
                        ->required(fn (Forms\Get $get): bool => $get('new_status') === PackageStatus::RECEIVED_IN_BUSINESS->value
                        )
                        ->maxLength(100),

                    Forms\Components\Textarea::make('note')
                        ->label('Nota (opcional)')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    $service = app(PackageService::class);
                    try {
                        $service->updatePackageStatus(
                            package: $this->record,
                            newStatus: $data['new_status'],
                            changedBy: auth()->id(),
                            note: $data['note'] ?? null,
                            shelfLocation: $data['shelf_location'] ?? null,
                        );
                        $this->refreshFormData(['status', 'shelf_location']);
                        Notification::make()
                            ->title('Estado actualizado')
                            ->success()
                            ->send();
                    } catch (DomainException $e) {
                        Notification::make()
                            ->title('Error al cambiar estado')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->hidden(fn (): bool => empty(
                    PackageStatus::from($this->record->status)->nextAllowedStatuses()
                )),

            Actions\Action::make('generar_factura')
                ->label(fn () => $this->record->hasInvoice() ? 'Reenviar Factura' : 'Generar Factura')
                ->icon('heroicon-o-document-text')
                ->color(fn () => $this->record->hasInvoice() ? 'gray' : 'success')
                ->visible(fn () => $this->record->status === PackageStatus::READY_TO_DELIVER->value)
                ->form(fn () => [
                    Forms\Components\TextInput::make('service_cost')
                        ->label('Costo del servicio (₡)')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default($this->record->service_cost),
                    Forms\Components\Placeholder::make('invoice_info')
                        ->label('Estado')
                        ->content(fn () => $this->record->hasInvoice()
                            ? "Factura {$this->record->invoice_number} ya generada. Se enviará nuevamente."
                            : 'Se generará una nueva factura.'),
                ])
                ->action(function (array $data) {
                    $service = app(InvoiceService::class);
                    try {
                        $service->generateAndPersistInvoice(
                            package: $this->record,
                            serviceCost: (float) $data['service_cost'],
                            adminId: auth()->id(),
                        );
                        $this->refreshFormData(['invoice_number', 'invoice_generated_at', 'service_cost', 'points_earned']);
                        Notification::make()
                            ->title('Factura generada')
                            ->body('La factura se enviará por correo al cliente.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al generar factura')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->hasInvoice() ? 'Reenviar Factura' : 'Generar Factura')
                ->modalDescription('El PDF se generará y enviará por correo al cliente.'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información del paquete')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('tracking')
                            ->label('Tracking')
                            ->fontFamily('mono')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => PackageResource::statusLabel($state))
                            ->color(fn (string $state): string => PackageResource::statusColor($state)),

                        Infolists\Components\TextEntry::make('shippingMethod.name')
                            ->label('Método de envío'),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Cliente'),

                        Infolists\Components\TextEntry::make('user.locker_code')
                            ->label('Casillero')
                            ->badge()
                            ->fontFamily('mono'),

                        Infolists\Components\TextEntry::make('shelf_location')
                            ->label('Estante')
                            ->placeholder('Sin asignar'),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('weight')
                            ->label('Peso')
                            ->suffix(' lbs')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('approx_value')
                            ->label('Valor aprox.')
                            ->prefix('$')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('service_cost')
                            ->label('Costo servicio')
                            ->prefix('₡')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('invoice_number')
                            ->label('Factura')
                            ->fontFamily('mono')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('points_earned')
                            ->label('Puntos otorgados')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('prealerted_at')
                            ->label('Prealertado el')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                Infolists\Components\Section::make('Historial de estados')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('statusHistories')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Fecha')
                                    ->dateTime('d/m/Y H:i')
                                    ->size('sm'),

                                Infolists\Components\TextEntry::make('from_status')
                                    ->label('De')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state ? PackageResource::statusLabel($state) : '—'
                                    )
                                    ->color(fn (?string $state): string => $state ? PackageResource::statusColor($state) : 'gray'
                                    )
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('to_status')
                                    ->label('A')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => PackageResource::statusLabel($state)
                                    )
                                    ->color(fn (string $state): string => PackageResource::statusColor($state)
                                    ),

                                Infolists\Components\TextEntry::make('changedBy.name')
                                    ->label('Por')
                                    ->placeholder('Sistema'),

                                Infolists\Components\TextEntry::make('note')
                                    ->label('Nota')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function nextStatusOptions(): array
    {
        $current = PackageStatus::from($this->record->status);
        $options = [];
        foreach ($current->nextAllowedStatuses() as $status) {
            $options[$status->value] = PackageResource::statusLabel($status->value);
        }

        return $options;
    }
}

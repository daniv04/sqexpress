<?php

namespace App\Filament\Resources;

use App\Enums\PackageStatus;
use App\Filament\Resources\PackageResource\Pages;
use App\Models\Package;
use App\Models\ShippingMethod;
use App\Services\DbService\PackageService;
use DomainException;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Paquetes';

    protected static ?string $modelLabel = 'Paquete';

    protected static ?string $pluralModelLabel = 'Paquetes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del paquete')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('tracking')
                            ->label('Tracking')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('shipping_method_id')
                            ->label('Método de envío')
                            ->options(ShippingMethod::where('active', true)->pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('user_id')
                            ->label('Cliente')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('description')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('weight')
                            ->label('Peso (lbs)')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('approx_value')
                            ->label('Valor aprox. (USD)')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('shelf_location')
                            ->label('Estante')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('status')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tracking')
                    ->label('Tracking')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.locker_code')
                    ->label('Casillero')
                    ->searchable()
                    ->badge()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::statusLabel($state))
                    ->color(fn (string $state): string => self::statusColor($state)),

                Tables\Columns\TextColumn::make('shippingMethod.name')
                    ->label('Método')
                    ->sortable(),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Peso')
                    ->suffix(' lbs')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('shelf_location')
                    ->label('Estante')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('prealerted_at')
                    ->label('Prealertado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(self::allStatusOptions()),

                SelectFilter::make('shipping_method_id')
                    ->label('Método de envío')
                    ->relationship('shippingMethod', 'name'),

                Tables\Filters\Filter::make('prealerted_at')
                    ->label('Rango de fechas')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('prealerted_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('prealerted_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('cambiar_estado')
                    ->label('Cambiar estado')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form(fn (Package $record): array => [
                        Forms\Components\Select::make('new_status')
                            ->label('Nuevo estado')
                            ->options(self::nextStatusOptions($record))
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
                    ->action(function (Package $record, array $data) {
                        $service = app(PackageService::class);
                        try {
                            $service->updatePackageStatus(
                                package: $record,
                                newStatus: $data['new_status'],
                                changedBy: auth()->id(),
                                note: $data['note'] ?? null,
                                shelfLocation: $data['shelf_location'] ?? null,
                            );
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
                    ->hidden(fn (Package $record): bool => empty(
                        PackageStatus::from($record->status)->nextAllowedStatuses()
                    )),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'view' => Pages\ViewPackage::route('/{record}'),
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private static function nextStatusOptions(Package $record): array
    {
        $current = PackageStatus::from($record->status);
        $options = [];
        foreach ($current->nextAllowedStatuses() as $status) {
            $options[$status->value] = self::statusLabel($status->value);
        }

        return $options;
    }

    private static function allStatusOptions(): array
    {
        $options = [];
        foreach (PackageStatus::cases() as $status) {
            $options[$status->value] = self::statusLabel($status->value);
        }

        return $options;
    }

    public static function statusLabel(string $state): string
    {
        return match ($state) {
            'prealerted' => 'Prealertado',
            'received_in_warehouse' => 'Recibido en bodega',
            'assigned_flight' => 'Vuelo asignado',
            'received_in_customs' => 'Recibido en aduana CR',
            'received_in_business' => 'Recibido en empresa',
            'ready_to_deliver' => 'Listo para entregar',
            'delivered' => 'Entregado',
            'canceled' => 'Cancelado',
            default => $state,
        };
    }

    public static function statusColor(string $state): string
    {
        return match ($state) {
            'prealerted' => 'gray',
            'received_in_warehouse' => 'info',
            'assigned_flight' => 'info',
            'received_in_customs' => 'warning',
            'received_in_business' => 'warning',
            'ready_to_deliver' => 'success',
            'delivered' => 'success',
            'canceled' => 'danger',
            default => 'gray',
        };
    }
}

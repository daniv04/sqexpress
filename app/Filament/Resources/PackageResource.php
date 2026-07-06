<?php

namespace App\Filament\Resources;

use App\Enums\PackageStatus;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PackageResource\Pages;
use App\Models\AppSetting;
use App\Models\Package;
use App\Models\ShippingMethod;
use App\Services\DbService\PackageService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
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
                            ->label('Peso (kg)')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('approx_value')
                            ->label('Valor aprox. (USD)')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('shelf_location')
                            ->label('Estante')
                            ->maxLength(100),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(collect(PackageStatus::cases())->mapWithKeys(
                                fn (PackageStatus $s) => [$s->value => $s->label()]
                            ))
                            ->required()
                            ->default(PackageStatus::PREALERTED->value)
                            ->visibleOn('create'),

                        Forms\Components\TextInput::make('status')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
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
                    ->suffix(' kg')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('shelf_location')
                    ->label('Estante')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('prealerted_at')
                    ->label('Prealertado')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Factura')
                    ->placeholder('—')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('invoice_generated_at')
                    ->label('Facturado')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                                changedBy: Auth::id(),
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

                Tables\Actions\Action::make('editar')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->authorize(fn (Package $record): bool => (bool) auth()->user()?->can('update', $record))
                    ->visible(fn (): bool => auth()->user()?->role === 'admin')
                    ->form(fn (): array => self::adminEditFormSchema())
                    ->fillForm(fn (Package $record): array => [
                        'tracking' => $record->tracking,
                        'description' => $record->description,
                        'weight' => $record->weight,
                        'approx_value' => $record->approx_value,
                        'shelf_location' => $record->shelf_location,
                        'shipping_method_id' => $record->shipping_method_id,
                    ])
                    ->action(function (Package $record, array $data, $livewire): void {
                        $livewire->mountTableAction('confirmar_edicion', $record->getKey(), ['data' => $data]);
                    })
                    ->registerModalActions([
                        Tables\Actions\Action::make('confirmar_edicion')
                            ->label('Confirmar cambios')
                            ->requiresConfirmation()
                            ->cancelParentActions()
                            ->modalHeading('Confirmar edición del paquete')
                            ->modalDescription(fn (Package $record, array $arguments): HtmlString => self::buildEditDiffDescription($record, $arguments['data'] ?? []))
                            ->authorize(fn (Package $record): bool => (bool) auth()->user()?->can('update', $record))
                            ->action(function (Package $record, array $arguments): void {
                                $service = app(PackageService::class);
                                $service->adminUpdatePackage($record, $arguments['data'] ?? [], Auth::id());

                                Notification::make()
                                    ->title('Paquete actualizado')
                                    ->success()
                                    ->send();
                            }),
                    ]),

                Tables\Actions\Action::make('eliminar')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->authorize(fn (Package $record): bool => (bool) auth()->user()?->can('delete', $record))
                    ->visible(fn (): bool => auth()->user()?->role === 'admin')
                    ->requiresConfirmation()
                    ->modalHeading('¿Seguro que deseas eliminar este paquete?')
                    ->action(function (Package $record, $livewire): void {
                        $livewire->mountTableAction('confirmar_eliminacion', $record->getKey());
                    })
                    ->registerModalActions([
                        Tables\Actions\Action::make('confirmar_eliminacion')
                            ->label('Eliminar definitivamente')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->cancelParentActions()
                            ->modalHeading('Esta acción es irreversible')
                            ->modalDescription('El paquete se eliminará permanentemente y no podrá recuperarse. El cliente será notificado por correo electrónico.')
                            ->authorize(fn (Package $record): bool => (bool) auth()->user()?->can('delete', $record))
                            ->action(function (Package $record): void {
                                $service = app(PackageService::class);
                                try {
                                    $service->adminDeletePackage($record, Auth::id());

                                    Notification::make()
                                        ->title('Paquete eliminado')
                                        ->success()
                                        ->send();
                                } catch (DomainException $e) {
                                    Notification::make()
                                        ->title('No se pudo eliminar')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ]),

                Tables\Actions\Action::make('crear_factura')
                    ->label('Crear Factura')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(fn (Package $record): bool =>
                        $record->status === PackageStatus::READY_TO_DELIVER->value && !$record->hasInvoice()
                    )
                    ->url(fn (Package $record): string =>
                        InvoiceResource::getUrl('create') . '?user_id=' . $record->user_id
                    ),

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
            'create' => Pages\CreatePackage::route('/create'),
            'view' => Pages\ViewPackage::route('/{record}'),
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public static function adminEditFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('tracking')
                ->label('Tracking')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\Select::make('shipping_method_id')
                ->label('Método de envío')
                ->options(ShippingMethod::where('active', true)->pluck('name', 'id'))
                ->required(),

            Forms\Components\TextInput::make('description')
                ->label('Descripción')
                ->required()
                ->maxLength(500)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('weight')
                ->label('Peso (kg)')
                ->numeric()
                ->minValue(0),

            Forms\Components\TextInput::make('approx_value')
                ->label('Valor aprox. (USD)')
                ->numeric()
                ->minValue(0),

            Forms\Components\TextInput::make('shelf_location')
                ->label('Estante')
                ->maxLength(100),
        ];
    }

    private static function fieldLabel(string $field): string
    {
        return match ($field) {
            'tracking' => 'Tracking',
            'description' => 'Descripción',
            'weight' => 'Peso (kg)',
            'approx_value' => 'Valor aprox. (USD)',
            'shelf_location' => 'Estante',
            'shipping_method_id' => 'Método de envío',
            default => $field,
        };
    }

    private static function buildEditDiffDescription(Package $record, array $data): HtmlString
    {
        $whitelist = ['tracking', 'description', 'weight', 'approx_value', 'shelf_location', 'shipping_method_id'];
        $decimalFields = ['weight', 'approx_value'];
        $lines = [];

        foreach (array_intersect_key($data, array_flip($whitelist)) as $field => $newValue) {
            $oldValue = $record->getAttribute($field);

            $isDifferent = in_array($field, $decimalFields, true)
                ? round((float) $oldValue, 2) !== round((float) $newValue, 2)
                : (string) $oldValue !== (string) $newValue;

            if (! $isDifferent) {
                continue;
            }

            if ($field === 'shipping_method_id') {
                $oldValue = ShippingMethod::find($oldValue)?->name ?? $oldValue;
                $newValue = ShippingMethod::find($newValue)?->name ?? $newValue;
            }

            $lines[] = e(self::fieldLabel($field)) . ': ' . e($oldValue) . ' → ' . e($newValue);
        }

        return new HtmlString($lines === [] ? 'No se detectaron cambios.' : implode('<br>', $lines));
    }

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
            'assigned_flight' => 'Asignado a vuelo',
            'in_transit' => 'En tránsito a CR',
            'received_in_customs' => 'Recibido en aduana CR',
            'customs_process_finished' => 'Procesos de aduanas finalizado',
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
            'in_transit' => 'info',
            'received_in_customs' => 'warning',
            'customs_process_finished' => 'warning',
            'received_in_business' => 'warning',
            'ready_to_deliver' => 'success',
            'delivered' => 'success',
            'canceled' => 'danger',
            default => 'gray',
        };
    }
}

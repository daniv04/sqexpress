<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Enums\PackageStatus;
use App\Filament\Resources\InvoiceResource;
use App\Models\AppSetting;
use App\Models\Package;
use App\Models\User;
use App\Services\DbService\InvoiceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cliente')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Cliente')
                            ->options(User::where('role', '!=', 'admin')
                                ->whereHas('packages', fn ($query) => $query
                                    ->where('status', PackageStatus::READY_TO_DELIVER->value)
                                    ->whereNull('invoice_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('package_ids', []))
                            ->default(request('user_id')),
                    ]),

                Forms\Components\Section::make('Paquetes a incluir')
                    ->schema([
                        Forms\Components\CheckboxList::make('package_ids')
                            ->label('Selecciona los paquetes')
                            ->required()
                            ->options(function (Forms\Get $get): array {
                                $userId = $get('user_id');
                                if (! $userId) {
                                    return [];
                                }

                                return Package::where('user_id', $userId)
                                    ->where('status', PackageStatus::READY_TO_DELIVER->value)
                                    ->whereNull('invoice_id')
                                    ->with('shippingMethod')
                                    ->get()
                                    ->mapWithKeys(function ($p) {
                                        $weight = $p->weight ?? 'SIN PESO';
                                        $unit = $p->shippingMethod?->unitSuffix() ?? 'kg';
                                        $cost = $p->service_cost ?? 0;

                                        return [
                                            $p->id => "#{$p->tracking} — {$p->description} ({$weight} {$unit}) — \$".number_format($cost, 2),
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $packages = Package::whereIn('id', $state)->with('shippingMethod')->get();
                                    $packageWeights = [];
                                    foreach ($packages as $p) {
                                        $weight = $p->weight;
                                        $price = (float) ($p->shippingMethod->price_per_unit ?? 0);
                                        $serviceCost = ($weight && $price > 0) ? round($weight * $price, 2) : null;
                                        $packageWeights[] = [
                                            'id' => $p->id,
                                            'tracking' => $p->tracking,
                                            'weight' => $weight,
                                            'service_cost' => $serviceCost,
                                            'unit_suffix' => $p->shippingMethod?->unitSuffix() ?? 'kg',
                                        ];
                                    }
                                    $set('package_weights', $packageWeights);
                                }
                            })
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('user_id'))
                            ->minItems(1)
                            ->helperText('Solo se muestran paquetes en estado "Listo para Entregar" sin factura previa.'),
                    ]),

                Forms\Components\Section::make('Pesos y costos')
                    ->visible(fn (Forms\Get $get): bool => ! empty($get('package_ids')))
                    ->schema([
                        Forms\Components\Repeater::make('package_weights')
                            ->label('Ajusta el peso de cada paquete')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->dehydrated(true)
                            ->schema([
                                Forms\Components\Hidden::make('id'),

                                Forms\Components\Hidden::make('unit_suffix')
                                    ->dehydrated(true),

                                Forms\Components\TextInput::make('tracking')
                                    ->label('Tracking')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('weight')
                                    ->label('Cantidad')
                                    ->suffix(fn (Forms\Get $get) => $get('unit_suffix') ?? 'kg')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        $id = $get('id');
                                        $package = $id ? Package::with('shippingMethod')->find($id) : null;
                                        $price = (float) ($package?->shippingMethod->price_per_unit ?? 0);
                                        $cost = ($state && $price > 0)
                                            ? round($state * $price, 2)
                                            : 0;
                                        $set('service_cost', $cost);
                                    }),

                                Forms\Components\TextInput::make('service_cost')
                                    ->label('Costo (USD)')
                                    ->numeric()
                                    ->readOnly()
                                    ->dehydrated(true),
                            ])
                            ->columns(3),
                    ]),

                Forms\Components\Section::make('Costos adicionales')
                    ->schema([
                        Forms\Components\Toggle::make('apply_new_client_discount')
                            ->label('Aplicar descuento de cliente nuevo (10%)')
                            ->helperText('Solo aplica en la primera factura del cliente.')
                            ->default(true)
                            ->live()
                            ->visible(function (Forms\Get $get): bool {
                                $userId = $get('user_id');

                                return $userId
                                    && app(InvoiceService::class)->isFirstInvoice(User::find($userId));
                            }),

                        Forms\Components\Toggle::make('has_delivery_fee')
                            ->label('Cobrar por entrega a domicilio')
                            ->live(),

                        Forms\Components\TextInput::make('delivery_fee')
                            ->label('Cargo por entrega (₡)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live()
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('has_delivery_fee')),
                    ]),

                Forms\Components\Section::make('Resumen de factura')
                    ->schema([
                        Forms\Components\Placeholder::make('subtotal_preview')
                            ->label('Subtotal (suma de paquetes)')
                            ->content(function (Forms\Get $get): string {
                                $packageWeights = $get('package_weights') ?? [];
                                $subtotal = 0;
                                foreach ($packageWeights as $item) {
                                    $subtotal += (float) ($item['service_cost'] ?? 0);
                                }

                                return '$'.number_format($subtotal, 2);
                            })
                            ->live(),

                        Forms\Components\Placeholder::make('discount_preview')
                            ->label('Descuento')
                            ->content(function (Forms\Get $get): string {
                                $userId = $get('user_id');
                                if (! $userId) {
                                    return '—';
                                }

                                $packageWeights = $get('package_weights') ?? [];
                                $subtotal = 0;
                                foreach ($packageWeights as $item) {
                                    $subtotal += (float) ($item['service_cost'] ?? 0);
                                }

                                $user = User::find($userId);
                                $service = app(InvoiceService::class);
                                $isFirst = $service->isFirstInvoice($user);
                                $applies = $isFirst && $get('apply_new_client_discount');
                                $discount = $service->calculateDiscount($subtotal, $applies);

                                if ($applies) {
                                    return '10% cliente nuevo: -$'.number_format($discount, 2);
                                }

                                return $isFirst ? 'Desactivado por el administrador' : 'Sin descuento';
                            })
                            ->live(),

                        Forms\Components\Placeholder::make('delivery_preview')
                            ->label('Cargo por entrega')
                            ->content(function (Forms\Get $get): string {
                                $hasDeliveryFee = $get('has_delivery_fee');
                                if (! $hasDeliveryFee) {
                                    return '—';
                                }

                                $deliveryFee = (float) ($get('delivery_fee') ?? 0);

                                return '₡'.number_format($deliveryFee, 2);
                            })
                            ->live(),

                        Forms\Components\Placeholder::make('total_preview')
                            ->label('Total')
                            ->content(function (Forms\Get $get): string {
                                $userId = $get('user_id');
                                if (! $userId) {
                                    return '—';
                                }

                                $packageWeights = $get('package_weights') ?? [];
                                $subtotal = 0;
                                foreach ($packageWeights as $item) {
                                    $subtotal += (float) ($item['service_cost'] ?? 0);
                                }

                                $user = User::find($userId);
                                $service = app(InvoiceService::class);
                                $applies = $get('apply_new_client_discount') && $service->isFirstInvoice($user);
                                $discount = $service->calculateDiscount($subtotal, $applies);
                                $deliveryFee = (float) ($get('has_delivery_fee') ? ($get('delivery_fee') ?? 0) : 0);
                                $total = $subtotal - $discount; // USD total excludes delivery (delivery is charged in CRC)

                                $totalPreview = '$'.number_format($total, 2);

                                $rate = (float) AppSetting::get('exchange_rate_usd_crc', 0);
                                if ($rate > 0 || $deliveryFee > 0) {
                                    $totalCrc = round(($rate > 0 ? $total * $rate : 0) + $deliveryFee, 2);
                                    $totalPreview .= ' · ₡'.number_format($totalCrc, 2);
                                }

                                return $totalPreview;
                            })
                            ->live(),
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::findOrFail($data['user_id']);
        $deliveryFee = $data['has_delivery_fee'] ? (float) ($data['delivery_fee'] ?? 0) : 0.0;

        $packageData = $data['package_weights'] ?? [];

        $packagesById = Package::whereIn('id', collect($packageData)->pluck('id')->filter())
            ->with('shippingMethod')
            ->get()
            ->keyBy('id');

        foreach ($packageData as $item) {
            $packageId = $item['id'] ?? null;
            $weight = (float) ($item['weight'] ?? 0);
            $cost = (float) ($item['service_cost'] ?? 0);

            if (! $packageId || $weight <= 0) {
                continue;
            }

            // Recalcular costo si no llegó correcto
            if ($cost <= 0) {
                $price = (float) ($packagesById[$packageId]->shippingMethod->price_per_unit ?? 0);
                if ($price > 0) {
                    $cost = round($weight * $price, 2);
                }
            }

            Package::where('id', $packageId)->update([
                'weight' => $weight,
                'service_cost' => $cost,
            ]);
        }

        $packageIds = collect($packageData)->pluck('id')->filter()->all();
        $packages = Package::whereIn('id', $packageIds)->get();

        return app(InvoiceService::class)->generateAndPersistInvoice(
            user: $user,
            packages: $packages,
            deliveryFee: $deliveryFee,
            adminId: auth()->id(),
            applyNewClientDiscount: (bool) ($data['apply_new_client_discount'] ?? true),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

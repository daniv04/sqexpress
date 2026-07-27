<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Configuracion extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $title = 'Configuración';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.configuracion';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'exchange_rate_usd_crc' => AppSetting::get('exchange_rate_usd_crc', 0),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('exchange_rate_usd_crc')
                    ->label('Tipo de cambio (USD → CRC)')
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix('₡')
                    ->helperText('Tipo de cambio aplicado al generar facturas (colones por 1 USD). Se congela en cada factura.'),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        AppSetting::set('exchange_rate_usd_crc', $data['exchange_rate_usd_crc']);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }
}

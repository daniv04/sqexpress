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
            'price_per_kg' => AppSetting::get('price_per_kg', 0),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('price_per_kg')
                    ->label('Precio por kilogramo (₡)')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('₡')
                    ->helperText('Este precio se usará para calcular el costo del servicio al generar facturas.'),
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

        AppSetting::set('price_per_kg', $data['price_per_kg']);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }
}

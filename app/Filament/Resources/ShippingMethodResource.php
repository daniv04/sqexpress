<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingMethodResource\Pages;
use App\Models\ShippingMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Métodos de envío';

    protected static ?string $modelLabel = 'Método de envío';

    protected static ?string $pluralModelLabel = 'Métodos de envío';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(true),

                Forms\Components\Section::make('Datos de dirección del casillero')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('nombre_en_campo')
                            ->label('Nombre del usuario va en')
                            ->options([
                                'nombre' => 'Nombre',
                                'apellido' => 'Apellido',
                            ])
                            ->hint('Define en qué campo se coloca el nombre del usuario'),

                        Forms\Components\TextInput::make('complemento_nombre')
                            ->label('Complemento de nombre (ej: SQE CR)'),

                        Forms\Components\TextInput::make('pais')
                            ->label('País'),

                        Forms\Components\TextInput::make('direccion')
                            ->label('Dirección')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('estado')
                            ->label('Estado / Departamento'),

                        Forms\Components\TextInput::make('ciudad')
                            ->label('Ciudad'),

                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono'),

                        Forms\Components\TextInput::make('codigo_postal')
                            ->label('Código Postal'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('packages_count')
                    ->label('Paquetes')
                    ->counts('packages')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (ShippingMethod $record): bool => $record->packages()->exists()),
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
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Barrio;
use App\Models\Canton;
use App\Models\Distrito;
use App\Models\Provincia;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos personales')
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->nullable()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('cedula')
                            ->label('Cédula')
                            ->nullable()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Ubicación')
                    ->icon('heroicon-o-map-pin')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('provincia_id')
                            ->label('Provincia')
                            ->options(Provincia::orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('canton_id', null);
                                $set('distrito_id', null);
                            }),

                        Forms\Components\Select::make('canton_id')
                            ->label('Cantón')
                            ->options(fn (Forms\Get $get) => $get('provincia_id')
                                ? Canton::where('provincia_id', $get('provincia_id'))->orderBy('nombre')->pluck('nombre', 'id')
                                : [])
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('distrito_id', null)),

                        Forms\Components\Select::make('distrito_id')
                            ->label('Distrito')
                            ->options(fn (Forms\Get $get) => $get('canton_id')
                                ? Distrito::where('canton_id', $get('canton_id'))->orderBy('nombre')->pluck('nombre', 'id')
                                : [])
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('barrio_id', null)),

                        Forms\Components\Select::make('barrio_id')
                            ->label('Barrio')
                            ->options(fn (Forms\Get $get) => $get('distrito_id')
                                ? Barrio::where('distrito_id', $get('distrito_id'))->orderBy('nombre')->pluck('nombre', 'id')
                                : [])
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Textarea::make('address')
                            ->label('Dirección exacta')
                            ->nullable()
                            ->maxLength(500)
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Acceso y seguridad')
                    ->icon('heroicon-o-lock-closed')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->minLength(5)
                            ->confirmed()
                            ->required(fn (string $context) => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->label(fn (string $context) => $context === 'edit'
                                ? 'Nueva Contraseña (dejar en blanco para no cambiar)'
                                : 'Contraseña'),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->password()
                            ->required(fn (string $context) => $context === 'create')
                            ->dehydrated(false)
                            ->label('Confirmar Contraseña'),

                        Forms\Components\Select::make('role')
                            ->label('Rol')
                            ->options([
                                'admin' => 'Admin',
                                'user' => 'Usuario',
                            ])
                            ->default('user')
                            ->required(),

                        Forms\Components\Toggle::make('active')
                            ->label('Activo')
                            ->default(true)
                            ->inline(false),
                    ]),

                Forms\Components\Section::make('Casillero y fidelidad')
                    ->icon('heroicon-o-archive-box')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('locker_code')
                            ->label('Código de Casillero (opcional)')
                            ->nullable()
                            ->placeholder('Dejar en blanco para generar automáticamente')
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('loyalty_points')
                            ->label('Puntos de Fidelidad')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0)
                            ->visibleOn('edit'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Buscar...')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('locker_code')
                    ->label('Casillero')
                    ->badge()
                    ->fontFamily('mono')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'purple',
                        'user' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Admin',
                        'user' => 'Usuario',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_points')
                    ->label('Puntos')
                    ->badge()
                    ->color('warning')
                    ->suffix(' pts')
                    ->sortable(false),

                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        'admin' => 'Admin',
                        'user' => 'Usuario',
                    ]),

                TernaryFilter::make('active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (User $record) => $record->id === auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                ->label('Nombre')
                ->required(),

            TextInput::make('email')
                ->email()
                ->unique(ignoreRecord: true)
                ->required(),

            TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->label(fn (string $operation) => $operation === 'create' ? 'Contraseña' : 'Nueva contraseña (dejar vacío para no cambiar)'),

            Select::make('roles')
                ->label('Rol')
                ->options(Role::pluck('name', 'name'))
                ->multiple(false)
                ->required()
                ->dehydrated(false) // no va al fillable directamente
                ->afterStateHydrated(function ($component, $record) {
                    if ($record) {
                        $component->state($record->roles->first()?->name);
                    }
                }),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Laravel\Pail\File;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('oferta_id')
                    ->required()
                    ->numeric(),
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('telefono')
                    ->tel(),
                TextInput::make('datos_extraidos')
                    ->required(),
                Textarea::make('analisis_ia')
                    ->required()
                    ->columnSpanFull(),
                Select::make('apto')
                    ->label('¿Es apto?')
                    ->options([
                        1 => 'Sí',
                        0 => 'No',
                    ])
                    ->required(),
                FileUpload::make('cv_path')
                    ->label('CV del candidato')
                    ->directory('cv-procesar')
                    ->disk('public')
                    ->required(),
            ]);
    }
}

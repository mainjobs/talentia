<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                Section::make('Información del Candidato')
                    ->schema([
                        TextEntry::make('nombre')
                            ->weight('bold'),
                        TextEntry::make('email')
                            ->label('Correo electrónico')
                            ->placeholder('-'),
                        TextEntry::make('telefono')
                            ->placeholder('-'),
                        TextEntry::make('oferta.titulo')
                            ->label('Oferta aplicada'),
                        TextEntry::make('analisis_ia')
                            ->label('Análisis de Gemini')
                            ->markdown() // Por si Gemini devuelve negritas o listas
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Fecha de registro')
                            ->dateTime()
                            ->size('sm'),
                        TextEntry::make('cv_path')
                        ->label('Currículum')
                        ->formatStateUsing(fn () => 'Abrir CV')
                        //->icon('hero-document-text')
                        ->color('primary')
                        ->url(fn ($state) => Storage::url($state), shouldOpenInNewTab: true)
                    ]),
                
            ]);
    }
}
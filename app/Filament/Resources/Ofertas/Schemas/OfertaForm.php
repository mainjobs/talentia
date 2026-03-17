<?php

namespace App\Filament\Resources\Ofertas\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema; // Importante
use Illuminate\Database\Eloquent\Builder;

class OfertaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Configuración de la Oferta')
                    ->description('Define el título y los criterios que Gemini usará para filtrar.')
                    ->schema([
                        TextInput::make('titulo')
                            ->required()
                            ->placeholder('Ej: Senior Laravel Developer'),
                        TextInput::make('descripcion')
                            ->required()
                            ->placeholder('Ej: Buscamos un desarrollador con experiencia en Laravel para un proyecto de 6 meses...'),
                        
                        Textarea::make('criterios_filtrado')
                            ->label('Criterios de Selección (Prompt)')
                            ->required()
                            ->rows(6)
                            ->placeholder('Ej: El candidato debe tener al menos 3 años de experiencia en PHP...'),
                        Select::make('ai_model')
                            ->label('Modelo de IA')
                            ->options([
                                'gpt-4o-mini'      => 'OpenAI GPT-4o Mini (rápido)',
                                'gpt-4o'           => 'OpenAI GPT-4o (preciso)',
                                'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                                'gemini-1.5-pro'   => 'Gemini 1.5 Pro',
                            ])
                            ->default('gpt-4o-mini')
                            ->visible(fn () => auth()->user()->email === 'daveloza@grupomainjobs.com')
                            ->helperText('Solo visible para administradores.'),
                    ]),
                Section::make('Sincronización con plataformas externas')
                    ->description('Configura la conexión con sistemas externos (CRM, LMS, etc.)')
                    ->schema([
                        Toggle::make('sync_with_crm')
                            ->label('¿Sincronizar con plataforma externa?')
                            ->inline(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (!$state) {
                                    $set('external_platform_id', null);
                                    $set('source_student_id', null);
                                    $set('propietario_clientify', null);
                                    $set('etiqueta_clientify', null);
                                }
                            })
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                Select::make('external_platform_id')
                                    ->label('Plataforma externa')
                                    ->relationship('externalPlatform', 'name', fn ($query) => $query->where('active', true))
                                    ->placeholder('Selecciona una plataforma')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn (Get $get): bool => (bool) $get('sync_with_crm'))
                                    ->disabled(fn (Get $get): bool => !$get('sync_with_crm'))
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('source_student_id', null);
                                        $set('propietario_clientify', null);
                                        $set('etiqueta_clientify', null);
                                    })
                                    ->helperText(fn (Get $get): ?string => 
                                        !$get('sync_with_crm') ? 'Activa la sincronización primero' : null
                                    ),

                                Select::make('source_student_id')
                                    ->label('Subcuenta de origen')
                                    ->relationship(
                                        'sourceStudent', 
                                        'description',
                                        fn (Builder $query, Get $get) => $query
                                            ->where('external_platform_id', $get('external_platform_id'))
                                            ->where('active', true)
                                    )
                                    ->placeholder('Selecciona una subcuenta')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn (Get $get): bool => (bool) $get('sync_with_crm'))
                                    ->live()
                                    ->helperText(function (Get $get) {
                                        if (!$get('sync_with_crm')) return 'Activa la sincronización primero';
                                        if (!$get('external_platform_id')) return 'Selecciona una plataforma primero';
                                        return null;
                                    }),

                                TextInput::make('propietario_clientify')
                                    ->label('Propietario en Clientify')
                                    ->placeholder('Correo electrónico del propietario')
                                    ->email()
                                    ->required(fn (Get $get): bool => $get('sync_with_crm')),
                            ]),

                        Repeater::make('etiqueta_clientify')
                            ->label('Etiquetas en Clientify (Opcional)')
                            ->simple(
                                TextInput::make('value')
                                    ->label('Etiqueta')
                                    ->placeholder('Lead Kubo')
                                    ->required()
                                    ->maxLength(50)
                                    ->datalist([
                                        'Lead Kubo',
                                        'Web',
                                        'Alta Prioridad',
                                        'Oposiciones',
                                        'Másteres',
                                        'Formación Continua',
                                        'INFOCA',
                                        'EIP',
                                        'EIM',
                                        'Campaña 2026',
                                        'Redes Sociales',
                                        'Email Marketing',
                                    ])
                            )
                            ->addActionLabel('Añadir etiqueta')
                            ->reorderable()
                            ->defaultItems(0)
                            ->collapsible()
                            ->helperText(function (Get $get) {
                                if (!$get('sync_with_crm')) return 'Activa la sincronización para configurar etiquetas';
                                if (!$get('source_student_id')) return 'Selecciona una subcuenta para configurar etiquetas';
                                return 'Opcional. Haz clic en "Añadir etiqueta" y escribe o selecciona de las sugerencias';
                            })
                            ->disabled(fn (Get $get): bool => !$get('sync_with_crm') || !$get('source_student_id'))
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }
}
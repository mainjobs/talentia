<?php 
namespace App\Filament\Resources\Ofertas\Tables;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Models\Oferta;
use App\Services\GeminiCVService;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;

class OfertasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('titulo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('leads_aptos_count')
                    ->counts('leadsAptos')
                    ->label('Aptos')
                    ->badge()
                    ->color('success')
                    ->url(fn ($record) => LeadResource::getUrl('index', [
                        'filters[oferta_id][value]' => $record->id,
                        'filters[apto][value]'      => '1',
                    ])),
                TextColumn::make('leads_no_aptos_count')
                    ->counts('leadsNoAptos')
                    ->label('No aptos')
                    ->badge()
                    ->color('danger')
                    ->url(fn ($record) => LeadResource::getUrl('index', [
                        'filters[oferta_id][value]' => $record->id,
                        'filters[apto][value]'      => '0',
                    ])),
            ])
            // CAMBIO AQUÍ: de actions() a recordActions()
            ->recordActions([
                Action::make('importar_cvs')
                    ->label('Procesar CVs')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->schema([
                        FileUpload::make('archivos')
                            ->label('Seleccionar PDFs')
                            ->multiple()
                            ->directory('cv-procesar')
                            ->disk('public')
                            ->required()
                            ->maxSize(1024)
                            ->helperText('Solo se permiten archivos PDF de máximo 1 MB.')
                            ->extraAttributes(['accept' => '.pdf']) 
                            ->dehydrateStateUsing(fn ($state) => $state),
                    ])
                    ->action(function (array $data, Oferta $record, GeminiCVService $gemini): void {
                        $aptos = 0;

                        foreach ($data['archivos'] as $path) {

                            $resultado = $gemini->procesarCandidato($path, $record->criterios_filtrado);

                            if ($resultado) {
                                $existe = Lead::where('oferta_id', $record->id)
                                    ->where('email', $resultado['datos']['email'])
                                    ->exists();

                                if (!$existe) {
                                    Lead::firstOrCreate(
                                        [
                                            'oferta_id' => $record->id,
                                            'email' => $resultado['datos']['email'],
                                        ],
                                        [
                                            'nombre' => $resultado['datos']['nombre'],
                                            'telefono' => $resultado['datos']['telefono'],
                                            'datos_extraidos' => $resultado['datos'],
                                            'analisis_ia' => $resultado['motivo_decision'],
                                            'cv_path' => $path,
                                            'apto' => $resultado['apto'],
                                        ]
                                    );
                                }
                                $aptos++;
                            }
                        }

                        Notification::make()
                            ->title('Proceso finalizado')
                            ->success()
                            ->body("Se han guardado {$aptos} candidatos aptos.")
                            ->send();
                    }),
                EditAction::make(),
            ]);
    }
}
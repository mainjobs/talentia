<?php 
namespace App\Filament\Resources\Ofertas\Tables;

use App\Filament\Resources\Leads\LeadResource;
use App\Jobs\ProcesarCVJob;
use App\Models\Lead;
use App\Models\Oferta;
use App\Services\GeminiCVService;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

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
                    ->action(function (array $data, Oferta $record): void {

                        $despachados = 0;

                        foreach ($data['archivos'] as $path) {

                            $existe = Lead::where('oferta_id', $record->id)
                                ->where('cv_path', $path)
                                ->exists();

                            if (!$existe) {
                                Lead::create([
                                    'oferta_id' => $record->id,
                                    'cv_path'   => $path,
                                    'estado'    => 'pendiente',
                                ]);

                                $despachados++;
                            }
                        }

                        $leads  = $record->leads()->where('estado', 'pendiente')->get();
                        $chunks = $leads->chunk(10);
                        $jobs   = [];

                        foreach ($chunks as $index => $chunk) {
                            foreach ($chunk as $lead) {
                                $jobs[] = (new ProcesarCVJob($lead->id))
                                    ->delay(now()->addSeconds($index * 60));
                            }
                        }

                        if (!empty($jobs)) {
                            Bus::batch($jobs)
                                ->name("CVs Oferta #{$record->id} - " . now()->format('d/m/Y H:i'))
                                ->allowFailures()
                                ->finally(function (Batch $batch) use ($record) {
                                    $completados = Lead::where('oferta_id', $record->id)
                                        ->where('estado', 'completado')
                                        ->count();
                                    $errores = Lead::where('oferta_id', $record->id)
                                        ->where('estado', 'error')
                                        ->count();

                                    // Notificación a todos los usuarios del panel
                                    foreach (\App\Models\User::all() as $user) {
                                        Notification::make()
                                            ->title('Evaluación de CVs completada')
                                            ->body("Oferta: {$record->titulo} — ✅ {$completados} completados, ❌ {$errores} errores.")
                                            ->success()
                                            ->sendToDatabase($user);
                                    }
                                })
                                ->dispatch();
                        }

                        Notification::make()
                            ->title('CVs enviados a procesar')
                            ->success()
                            ->body("{$despachados} CVs nuevos añadidos a la cola.")
                            ->send();
                    })
                    ,
                EditAction::make(),
            ]);
    }
}
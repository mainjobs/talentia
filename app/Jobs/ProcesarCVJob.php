<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\CVServiceFactory;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcesarCVJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly int $leadId
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $lead = Lead::find($this->leadId);

        if (!$lead) {
            Log::info("Lead #{$this->leadId} ya no existe, saltando job.");
            return;
        }

        $lead->update(['estado' => 'procesando']);

        $model   = $lead->oferta->ai_model ?? 'gpt-4o-mini';
        $service = CVServiceFactory::make($model);

        try {
            $resultado = $service->procesarCandidato(
                $lead->cv_path,
                $lead->oferta->criterios_filtrado,
                $model
            );

            if (!$resultado) {
                $lead->update(['estado' => 'error']);
                return;
            }

            $emailDuplicado = Lead::where('oferta_id', $lead->oferta_id)
                ->where('email', $resultado['datos']['email'])
                ->where('id', '!=', $lead->id)
                ->exists();

            if ($emailDuplicado) {
                $lead->delete();
                return;
            }

            $lead->update([
                'nombre'            => $resultado['datos']['nombre']               ?? null,
                'email'             => $resultado['datos']['email']                ?? null,
                'telefono'          => $resultado['datos']['telefono']             ?? null,
                'ubicacion'         => $resultado['datos']['ubicacion']            ?? null,
                'edad'              => $resultado['datos']['edad']                 ?? null,
                'experiencia_anios' => $resultado['datos']['experiencia_anios']    ?? null,
                'datos_extraidos'   => $resultado['datos'],
                'analisis_ia'       => $resultado['motivo_decision']               ?? null,
                'resumen_perfil'    => $resultado['resumen_perfil']                ?? null,
                'puntos_fuertes'    => $resultado['datos']['puntos_fuertes']       ?? [],
                'puntos_debiles'    => $resultado['datos']['puntos_debiles']       ?? [],
                'apto'              => $resultado['apto'],
                'estado'            => 'completado',
            ]);

        } catch (\App\Exceptions\OpenAIRateLimitException | \App\Exceptions\GeminiRateLimitException $e) {
            $lead->update(['estado' => 'pendiente']);
            Log::warning("Rate limit en Lead #{$lead->id}, reencolar en 90s");
            self::dispatch($this->leadId)->delay(now()->addSeconds(90));
        }
    }

    public function failed(\Throwable $e): void
    {
        $lead = Lead::find($this->leadId);
        $lead?->update([
            'estado'      => 'error',
            'analisis_ia' => 'Error: ' . $e->getMessage(),
        ]);
    }
}
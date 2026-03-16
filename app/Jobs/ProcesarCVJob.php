<?php

namespace App\Jobs;

use App\Exceptions\GeminiRateLimitException;
use App\Models\Lead;
use App\Services\GeminiCVService;
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
    public int $backoff = 30; // segundos entre reintentos automáticos

    public function __construct(
        public readonly Lead $lead
    ) {}

    public function handle(GeminiCVService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $this->lead->update(['estado' => 'procesando']);

        try {
            $resultado = $service->procesarCandidato(
                $this->lead->cv_path,
                $this->lead->oferta->criterios_filtrado  // campo real de tu modelo
            );

            if (!$resultado) {
                $this->lead->update(['estado' => 'error']);
                return;
            }

            // Evitar duplicados por email dentro de la misma oferta
            $emailDuplicado = Lead::where('oferta_id', $this->lead->oferta_id)
                ->where('email', $resultado['datos']['email'])
                ->where('id', '!=', $this->lead->id)
                ->exists();

            if ($emailDuplicado) {
                $this->lead->delete();
                return;
            }

            $this->lead->update([
                'nombre'         => $resultado['datos']['nombre']     ?? null,
                'email'          => $resultado['datos']['email']      ?? null,
                'telefono'       => $resultado['datos']['telefono']   ?? null,
                'datos_extraidos' => $resultado['datos'],
                'analisis_ia'    => $resultado['motivo_decision']     ?? null,
                'apto'           => $resultado['apto'],
                'estado'         => 'completado',
            ]);

         } catch (GeminiRateLimitException $e) {
            // Volver a pendiente y reencolar con 90 segundos de espera
            $this->lead->update(['estado' => 'pendiente']);

            Log::warning("Rate limit en Lead #{$this->lead->id}, reencolar en 90s");

            self::dispatch($this->lead)->delay(now()->addSeconds(90));
        }   
    }

    public function failed(\Throwable $e): void
    {
        $this->lead->update([
            'estado'      => 'error',
            'analisis_ia' => 'Error tras ' . $this->tries . ' intentos: ' . $e->getMessage(),
        ]);
    }
}
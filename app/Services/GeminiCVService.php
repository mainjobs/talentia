<?php 

namespace App\Services;

use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Illuminate\Support\Facades\Log;
use Gemini\Laravel\Facades\Gemini;

class GeminiCVService
{
    public function procesarCandidato($path, $criterios, string $model = 'gemini-2.5-flash')
    {
        $fullPath = storage_path("app/public/{$path}");
        
        if (!file_exists($fullPath)) {
            Log::error("Archivo no encontrado: {$fullPath}");
            return null;
        }

        try {
            $pdfData = base64_encode(file_get_contents($fullPath));

            // Prompt optimizado para evitar alucinaciones
            $prompt = "Actúa como un reclutador. Evalúa el CV adjunto basándote en estos CRITERIOS: '{$criterios}'.
            Responde EXCLUSIVAMENTE un objeto JSON con esta estructura:
            {
                \"apto\": boolean,
                \"motivo_decision\": \"string\",
                \"datos\": {
                    \"nombre\": \"string\", \"email\": \"string\", \"telefono\": \"string\", \"titulacion\": \"string\"
                }
            }";

            $result = Gemini::generativeModel('models/' . $model)->generateContent([
                $prompt,
                new Blob(
                    mimeType: MimeType::APPLICATION_PDF,
                    data: $pdfData
                )
            ]);

            $rawText = $result->text();

            // LIMPIEZA DE JSON: Extraemos solo lo que está entre llaves { }
            // Esto evita errores si Gemini responde con "Aquí tienes el JSON: ```json ... ```"
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $rawText, $matches)) {
                $cleanJson = $matches[0];
            } else {
                $cleanJson = $rawText;
            }

            $datos = json_decode($cleanJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Error al decodificar JSON de Gemini: " . json_last_error_msg());
                return null;
            }

            // ✅ Guardamos SIEMPRE en el log, sea apto o no apto
            Log::channel('cv_descartados')->info('Evaluación de candidato', [
                'archivo'         => $path,
                'apto'            => $datos['apto'] ? 'SÍ' : 'NO',
                'motivo'          => $datos['motivo_decision'] ?? 'Sin motivo',
                'nombre'          => $datos['datos']['nombre'] ?? 'Desconocido',
                'email'           => $datos['datos']['email'] ?? '-',
                'telefono'        => $datos['datos']['telefono'] ?? '-',
                'titulacion'      => $datos['datos']['titulacion'] ?? '-',
            ]);

            return $datos;

        } catch (\Exception $e) {
            // Detectar rate limit por mensaje de excepción
            if ($this->esRateLimit($e)) {
                Log::warning("Rate limit Gemini para {$path}");
                throw new \App\Exceptions\GeminiRateLimitException($e->getMessage());
            }

            Log::error("Fallo en el servicio Gemini: " . $e->getMessage());
            throw $e;
        }
    }

    private function esRateLimit(\Exception $e): bool
    {
        $mensaje = strtolower($e->getMessage());
        return str_contains($mensaje, '429')
            || str_contains($mensaje, 'rate limit')
            || str_contains($mensaje, 'quota exceeded')
            || str_contains($mensaje, 'resource_exhausted');
    }
}
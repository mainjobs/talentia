<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;

class OpenAICVService
{
    public function procesarCandidato($path, $criterios, string $model = 'gpt-4o-mini')
    {
        $fullPath = storage_path("app/public/{$path}");

        if (!file_exists($fullPath)) {
            Log::error("Archivo no encontrado: {$fullPath}");
            return null;
        }

        try {
            $mimeType = mime_content_type($fullPath);
            $texto    = $this->extraerTexto($fullPath, $mimeType);

            if (empty(trim($texto))) {
                Log::error("No se pudo extraer texto del archivo: {$path}");
                return null;
            }

            $prompt = "Actúa como un reclutador experto. Analiza el CV adjunto basándote en estos CRITERIOS: '{$criterios}'.

            Responde EXCLUSIVAMENTE un objeto JSON con esta estructura, sin markdown ni texto adicional:
            {
                \"apto\": boolean,
                \"motivo_decision\": \"Explica detalladamente por qué el candidato ES APTO o NO ES APTO según los criterios. Sé específico y menciona los puntos clave que han determinado la decisión.\",
                \"resumen_perfil\": \"Descripción breve del perfil profesional del candidato en 2-3 frases.\",
                \"datos\": {
                    \"nombre\": \"string\",
                    \"email\": \"string\",
                    \"telefono\": \"string\",
                    \"titulacion\": \"string\",
                    \"edad\": \"string o null si no se puede determinar\",
                    \"ubicacion\": \"string o null si no se puede determinar\",
                    \"experiencia_anios\": \"string o null si no se puede determinar\",
                    \"experiencia_relevante\": \"Describe brevemente la experiencia más relevante del candidato para el puesto.\",
                    \"puntos_fuertes\": [\"lista\", \"de\", \"puntos\", \"fuertes\"],
                    \"puntos_debiles\": [\"lista\", \"de\", \"puntos\", \"débiles\", \"o\", \"carencias\"]
                }
            }";
            
            $response = OpenAI::chat()->create([
                'model'    => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user',   'content' => "CONTENIDO DEL CV:\n\n" . $texto],
                ],
                'temperature' => 0.1, // respuestas más deterministas
            ]);

            $rawText = $response->choices[0]->message->content;

            // Limpiar posibles markdown code blocks
            $rawText = preg_replace('/```json|```/i', '', $rawText);

            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $rawText, $matches)) {
                $cleanJson = $matches[0];
            } else {
                $cleanJson = $rawText;
            }

            $datos = json_decode($cleanJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Error al decodificar JSON de OpenAI: " . json_last_error_msg());
                return null;
            }

            Log::channel('cv_descartados')->info('Evaluación de candidato', [
                'archivo'    => $path,
                'proveedor'  => 'openai',
                'modelo'     => 'gpt-4o-mini',
                'apto'       => $datos['apto'] ? 'SÍ' : 'NO',
                'motivo'     => $datos['motivo_decision'] ?? 'Sin motivo',
                'nombre'     => $datos['datos']['nombre']     ?? 'Desconocido',
                'email'      => $datos['datos']['email']      ?? '-',
                'telefono'   => $datos['datos']['telefono']   ?? '-',
                'titulacion' => $datos['datos']['titulacion'] ?? '-',
            ]);

            return $datos;

        } catch (\Exception $e) {
            if ($this->esRateLimit($e)) {
                Log::warning("Rate limit OpenAI para {$path}");
                throw new \App\Exceptions\OpenAIRateLimitException($e->getMessage());
            }

            Log::error("Fallo en el servicio OpenAI: " . $e->getMessage());
            throw $e;
        }
    }

    private function extraerTexto(string $fullPath, string $mimeType): string
    {
        // PDF
        if ($mimeType === 'application/pdf') {
            $parser = new Parser();
            $pdf    = $parser->parseFile($fullPath);
            return $pdf->getText();
        }

        // Word .docx / .doc
        if (in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])) {
            return ''; //$this->extraerTextoWord($fullPath);
        }

        return '';
    }

    /*private function extraerTextoWord(string $fullPath): string
    {
        $phpWord = IOFactory::load($fullPath);
        $texto   = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $texto .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $texto .= $child->getText() . "\n";
                        }
                    }
                }
            }
        }

        return trim($texto);
    }*/

    private function esRateLimit(\Exception $e): bool
    {
        $mensaje = strtolower($e->getMessage());
        return str_contains($mensaje, '429')
            || str_contains($mensaje, 'rate limit')
            || str_contains($mensaje, 'quota exceeded')
            || str_contains($mensaje, 'too many requests');
    }
}
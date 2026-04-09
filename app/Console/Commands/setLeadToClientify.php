<?php

namespace App\Console\Commands;

use App\Mail\SyncException;
use App\Models\Lead;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class setLeadToClientify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set:clientify-deals {leadId} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send all deals in pipeline stage initial to Clientify';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Conectando con Clientify');

        $leadId = $this->argument('leadId');
        $lead = Lead::find($leadId);

        if (!$lead) {
            $this->error("Lead con ID {$leadId} no encontrado");
            return false;
        }

        $res = $this->createNewContactAndDeal($lead);

        if ($res) {
            $this->info('Se han enviado todos los leads actualizados');
        }
    }

    private function createNewContactAndDeal(Lead $lead) 
    {
        // 1. Identificación de la Oferta y validación de sincronización
        $offer = $lead->oferta;

        if(!$offer->sync_with_crm) {
            $this->info("La oferta '{$offer->titulo}' no está marcada para sincronización con Clientify. Se omite este lead.");
            return false;
        }

        // Cargar el propietario de Clientify
        $clientifyOwner = $offer->propietario_clientify;;
        
        if (!$clientifyOwner) {
            $this->error("Usuario propietario de Clientify no encontrado");
            return false;
        }

        $token = $offer->sourceStudent->token;

        if(!$token) {
            $this->error("Token de Clientify no configurado");
            return false;
        }

        $host = $offer->externalPlatform->url;

        if(!$host) {
            $this->error("URL base de Clientify no configurada");
            return false;
        }

        try {
            // PASO 1: CREAR CONTACTO
            $this->info("Creando contacto en Clientify...");
            
            // Preparar custom fields
            $customFields = [];
            
            //URL CV
            $customFields[] = [
                'field' => 'CV url',
                'value' => 'https://talentia.grupomainjobs.com/storage/' . $lead->cv_path,
            ];
            
            $contactData = [
                'first_name'  => $lead->nombre,
                'email'       => $lead->email,
                'status'      => 'warm-lead',
                'gdpr_accept' => true,
                'owner'       => $clientifyOwner,
                'CV url'      => 'https://talentia.grupomainjobs.com/storage/' . $lead->cv_path,
            ];

            if (!empty($lead->telefono)) {
                $contactData['phone'] = $lead->telefono;
            }
            
            // Solo añadir custom_fields si hay datos
            if (!empty($customFields)) {
                $contactData['custom_fields'] = $customFields;
            }
            
            $contactResponse = Http::withToken($token, 'token')->post("{$host}/v1/contacts/", $contactData);

            if (!$contactResponse->successful()) {
                $this->error("Error al crear contacto:");
                $this->error("  Status: " . $contactResponse->status());
                $this->error("  Body: " . $contactResponse->body());
                return false;
            }

            $contactUrl = $contactResponse->json()['url'];
            $contactId = $contactResponse->json('id');
            
            $this->info("✔ Contacto creado correctamente (ID: {$contactId})");
            
            if (!empty($customFields)) {
                $this->info("📝 Custom fields enviados:");
                foreach ($customFields as $cf) {
                    $this->info("  - {$cf['field']}: {$cf['value']}");
                }
            }

            // PASO 2: ASIGNAR ETIQUETAS
            $tags = $offer->etiqueta_clientify; // 🎯 Pasar tanto lead como project

            if (is_array($tags) && count($tags) > 0) {
                $this->info("🏷️  Asignando " . count($tags) . " etiqueta(s)");
                
                foreach ($tags as $tagName) {
                    $tagResponse = Http::withToken($token, 'token')
                        ->post("{$host}/v1/contacts/{$contactId}/tags/", [
                            'name' => $tagName,
                        ]);

                    if ($tagResponse->successful()) {
                        $this->info("  ✔ '{$tagName}' asignada");
                    } else {
                        $this->error("  ✗ Error al asignar '{$tagName}'");
                    }
                }
                
                $this->info("✔ Etiquetas procesadas correctamente");
            } else {
                $this->info("ℹ️  No hay etiquetas configuradas en el proyecto ni en el curso");
            }

            // PASO 3: CREAR DEAL
            $this->info("Creando deal en Clientify...");
            
            $dealData = [
                'name'          => $lead->nombre,
                'contact'       => $contactUrl,
                'amount'        => 0,
                'deal_source'   => 'TalentIA System',
                'owner'         => $clientifyOwner,
            ];
            
            $dealResponse = Http::withToken($token, 'token')->post("{$host}/v1/deals/", $dealData);

            if (!$dealResponse->successful()) {
                $this->error("Error al crear deal:");
                $this->error("  Status: " . $dealResponse->status());
                $this->error("  Body: " . $dealResponse->body());
                return false;
            }

            $dealId = $dealResponse->json()['id'];
            
            $this->info("✔ Deal creado correctamente (ID: {$dealId})");

            // PASO 4: ACTUALIZAR LEAD
            $lead->update([
                'in_clientify' => true,
                'clientify_deal_id' => $dealId,
                'synced_at' => now(),
            ]);
            
            $this->info("✔ Lead actualizado con fecha de sincronización: " . now()->format('d/m/Y H:i:s'));
            $this->info("
    === RESUMEN ===");
            $this->info("Contacto ID: {$contactId}");
            $this->info("Deal ID: {$dealId}");
            $this->info("Owner: {$clientifyOwner}");
            $this->info("Tags: " . (count($tags) > 0 ? implode(', ', $tags) : 'Ninguna'));
            $this->info("===============");
            
            return true;

        } catch (\Exception $e) {
            $this->error("Excepción: " . $e->getMessage());
            $this->error("Línea: " . $e->getLine());
            $this->error("Archivo: " . $e->getFile());
            
            Mail::to('daveloza@grupomainjobs.com')->queue(new SyncException($e->getMessage()));
            
            return false;
        }
    }

    /**
     * Preparar las etiquetas para el lead
     * Combina etiquetas del proyecto + etiquetas del curso
     * @return array
     */
    
    /**
     * TODO: Este método se puede optimizar para buscar directamente el pipeline "Sin gestionar" sin necesidad de cargar todos los pipelines y recorrerlos. Si Clientify tiene un endpoint para obtener un pipeline por nombre, sería ideal usarlo. Por ahora, se mantiene la lógica actual pero con mejoras en manejo de errores y logging.
     */
    private function getPipelineInitial(string $token, string $host)
    {
        $pipelineStage = [];
        $pipelines = $this->getPipelineStage($token, $host);

        $this->info("Buscando pipeline 'Sin gestionar'...");

        $pipelines->each(function ($pipe) use (&$pipelineStage) {
            try {
                if (str_contains(strtolower($pipe['name']), 'contacto realizado')) {
                    $pipelineStage = $pipe;
                    $this->info("✔ Pipeline detectado: " . $pipe['name']);
                    return false; // Detiene el loop
                }
            } catch (Throwable $e) {
                $this->error("Error al procesar pipeline: " . $e->getMessage());
                Mail::to('daveloza@grupomainjobs.com')->queue(new SyncException('Error en getPipelineInitial: ' . $e->getMessage()));
            } catch (Exception $ex) {
                $this->error("Excepción al procesar pipeline: " . $ex->getMessage());
                Mail::to('daveloza@grupomainjobs.com')->queue(new SyncException('Error en getPipelineInitial: ' . $ex->getMessage()));
            }
        });

        return $pipelineStage;
    }

    private function getPipelineStage(string $token, string $host)
    {
        $endpointPipeline = "{$host}/v1/deals/pipelines/stages/";
        $items = [];
        
        try {
            do {
                $this->info("Consultando: {$endpointPipeline}");
                
                $response = Http::withHeaders(['Authorization' => 'Token '.$token])->get($endpointPipeline);
                $data = $response->json();

                if (!isset($data['results'])) {
                    $this->error('La llamada a Clientify no devuelve resultados');
                    Mail::to('daveloza@grupomainjobs.com')->queue(
                        new SyncException('La llamada a Clientify no devuelve resultados en getPipelineStage')
                    );
                    break;
                }
                
                $items = array_merge($items, $data['results']);
                $this->info("Obtenidos " . count($data['results']) . " pipelines");

                // Actualizar el endpoint para la próxima página
                $endpointPipeline = $data['next'];
                
            } while (!empty($data['next']));
            
            $this->info("Total de pipelines obtenidos: " . count($items));
            
        } catch (Exception $e) {
            $this->error("Error al obtener pipelines: " . $e->getMessage());
            Mail::to('daveloza@grupomainjobs.com')->queue(
                new SyncException('Error en getPipelineStage: ' . $e->getMessage())
            );
        }

        return collect($items);
    }

    /**
     * Extraer valores de tags desde el formato del repeater
     * @param array $tagsData
     * @return array
     */
    private function extractTagsFromSource(array $tagsData): array
    {
        $tags = [];
        
        foreach ($tagsData as $item) {
            if (is_array($item) && isset($item['value'])) {
                $tagValue = trim($item['value']);
                if (!empty($tagValue)) {
                    $tags[] = $tagValue;
                }
            } else if (is_string($item)) {
                $tagValue = trim($item);
                if (!empty($tagValue)) {
                    $tags[] = $tagValue;
                }
            }
        }
        
        return $tags;
    }
}
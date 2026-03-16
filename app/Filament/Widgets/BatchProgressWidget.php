<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class BatchProgressWidget extends Widget
{
    protected string $view = 'filament.widgets.batch-progress-widget';
    protected static ?int $sort = 0; // primero en el dashboard
    protected int|string|array $columnSpan = 'full';

    // Polling cada 5 segundos
    protected string $pollingInterval = '5s';

    public function getBatches(): Collection
    {
        return DB::table('job_batches')
            ->orderByDesc('created_at')
            ->limit(5) // últimos 5 batches
            ->get()
            ->map(function ($batch) {
                $total      = $batch->total_jobs;
                $pendientes = $batch->pending_jobs;
                $fallidos   = $batch->failed_jobs;
                $completados = $total - $pendientes - $fallidos;
                $progreso   = $total > 0 ? round(($completados / $total) * 100) : 0;

                return [
                    'id'          => $batch->id,
                    'nombre'      => $batch->name,
                    'total'       => $total,
                    'completados' => $completados,
                    'pendientes'  => $pendientes,
                    'fallidos'    => $fallidos,
                    'progreso'    => $progreso,
                    'finalizado'  => !is_null($batch->finished_at),
                    'cancelado'   => !is_null($batch->cancelled_at),
                    'creado'      => \Carbon\Carbon::createFromTimestamp($batch->created_at)->diffForHumans(),
                ];
            });
    }
}
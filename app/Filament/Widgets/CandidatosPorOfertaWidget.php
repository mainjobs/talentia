<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Lead;
use App\Models\Oferta;

class CandidatosPorOfertaWidget extends ChartWidget
{
    protected ?string $heading = 'Candidatos por oferta';
    protected int|string|array $columnSpan = 2; // ocupa 2 columnas

    protected function getData(): array
    {
        $ofertas = Oferta::pluck('titulo', 'id');

        $aptos   = [];
        $noAptos = [];

        foreach ($ofertas as $id => $titulo) {
            $aptos[]   = Lead::where('oferta_id', $id)->where('apto', true)->count();
            $noAptos[] = Lead::where('oferta_id', $id)->where('apto', false)->count();
        }

        return [
            'labels'   => $ofertas->values()->toArray(),
            'datasets' => [
                [
                    'label'           => 'Aptos',
                    'data'            => $aptos,
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label'           => 'No aptos',
                    'data'            => $noAptos,
                    'backgroundColor' => '#ef4444',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true, 'beginAtZero' => true],
            ],
        ];
    }
}
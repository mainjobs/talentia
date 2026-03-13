<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Lead;

class TasaAptitudWidget extends ChartWidget
{
    protected ?string $heading = 'Ratio global de aptitud';
    //protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $aptos   = Lead::where('apto', true)->count();
        $noAptos = Lead::where('apto', false)->count();

        return [
            'labels'   => ['Aptos', 'No aptos'],
            'datasets' => [
                [
                    'data'            => [$aptos, $noAptos],
                    'backgroundColor' => ['#22c55e', '#ef4444'],
                    'borderWidth'     => 0,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
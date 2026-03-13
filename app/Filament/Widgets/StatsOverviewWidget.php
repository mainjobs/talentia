<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Lead;
use App\Models\Oferta;

class StatsOverviewWidget extends BaseWidget
{
    //protected ?int $sort = 1;

    protected function getStats(): array
    {
        $total    = Lead::count();
        $totalOfertas = Oferta::count();
        $aptos    = Lead::where('apto', true)->count();
        $noAptos  = Lead::where('apto', false)->count();
        $synced   = Lead::whereNotNull('in_clientify')->count();
        $tasa     = $total > 0 ? round(($aptos / $total) * 100, 1) : 0;

        return [
            Stat::make('Total CVs procesados', $total)
                ->icon('heroicon-o-document-text')
                ->color('gray'),

            Stat::make('Candidatos aptos', $aptos)
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Candidatos no aptos', $noAptos)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Tasa de aptitud', $tasa . '%')
                ->icon('heroicon-o-chart-pie')
                ->color('info'),

            Stat::make('Sincronizados con CRM', $synced)
                ->icon('heroicon-o-arrow-path')
                ->color('warning'),

            Stat::make('Total ofertas', $totalOfertas)
                ->icon('heroicon-o-briefcase')
                ->color('primary'),
        ];
    }
}
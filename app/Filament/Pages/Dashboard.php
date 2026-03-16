<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Lead;
use App\Models\Oferta;
use BackedEnum;

class Dashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected string $view = 'filament.pages.dashboard';
    protected static null|string $navigationLabel = 'Dashboard';
    protected static null|string $title = '';

    // Datos que se pasan a Blade
    public $aptos;
    public $noAptos;
    public $totalCVs;
    public $leadsSync;

    public function mount(): void
    {
        $this->aptos = Lead::where('apto', 1)->count();
        $this->noAptos = Lead::where('apto', 0)->count();
        $this->totalCVs = Lead::count();
        $this->leadsSync = Lead::whereNotNull('in_clientify')->count(); // ejemplo
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\BatchProgressWidget::class,
            \App\Filament\Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\CandidatosPorOfertaWidget::class,
            \App\Filament\Widgets\TasaAptitudWidget::class,
            \App\Filament\Widgets\UltimosLeadsWidget::class,
        ];
    }

    public function getStackedChartData(): array
    {
        $ofertas = Oferta::pluck('titulo', 'id');

        $aptos = [];
        $noAptos = [];

        foreach ($ofertas as $id => $nombre) {
            $aptos[] = Lead::where('oferta_id', $id)->where('apto', 1)->count();
            $noAptos[] = Lead::where('oferta_id', $id)->where('apto', 0)->count();
        }

        return [
            'labels' => $ofertas->values()->toArray(),
            'datasets' => [
                [
                    'label' => 'APTOS',
                    'backgroundColor' => '#22c55e', // verde
                    'data' => $aptos,
                ],
                [
                    'label' => 'NO APTOS',
                    'backgroundColor' => '#ef4444', // rojo
                    'data' => $noAptos,
                ],
            ],
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'sm'  => 1,
            'md'  => 2,
            'xl'  => 3,
        ];
    }

    // Definir la vista de Blade
    public function view(): string
    {
        return 'filament.pages.dashboard';
    }
}
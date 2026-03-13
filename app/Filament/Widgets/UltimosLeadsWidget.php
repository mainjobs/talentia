<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Lead;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class UltimosLeadsWidget extends BaseWidget
{
    protected static ?string $heading = 'Últimos candidatos procesados';
    //protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full'; // ancho completo

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lead::query()->latest()->limit(8)
            )
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),

                TextColumn::make('oferta.titulo')
                    ->label('Oferta')
                    ->badge(),

                IconColumn::make('apto')
                    ->label('Apto')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Procesado')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
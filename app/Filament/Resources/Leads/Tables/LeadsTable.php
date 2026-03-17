<?php

namespace App\Filament\Resources\Leads\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use SebastianBergmann\CodeCoverage\Filter;
use App\Jobs\ProcesarCVJob;
use App\Models\Lead;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Bus;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->selectable()
            ->columns([
                TextColumn::make('oferta.titulo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nombre')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->searchable(),
                // ✅ Columna visual para saber si ya está sincronizado
                IconColumn::make('in_clientify')
                    ->label('Clientify')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente'   => 'gray',
                        'procesando'  => 'warning',
                        'completado'  => 'success',
                        'error'       => 'danger',
                        default       => 'gray',
                    }),
                IconColumn::make('apto')
                    ->label('Apto')
                    ->boolean()
                    ->trueIcon('heroicon-o-check')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('synced_at')
                    ->label('Sincronizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('No sincronizado')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('oferta_id')
                    ->label('Oferta')
                    ->relationship('oferta', 'titulo')
                    ->placeholder('Todas las ofertas')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('apto')
                    ->label('Aptos')
                    ->trueLabel('Solo aptos')
                    ->falseLabel('Solo no aptos')
                    ->queries(
                        true: fn (Builder $query) => $query->where('apto', true),
                        false: fn (Builder $query) => $query->where('apto', false),
                        blank: fn (Builder $query) => $query,
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                // ✅ Botón de sincronización
                Action::make('sync_clientify')
                    ->label('Sincronizar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Sincronizar con Clientify')
                    ->modalDescription(fn ($record) => "¿Deseas sincronizar a {$record->nombre} con Clientify?")
                    ->modalSubmitActionLabel('Sí, sincronizar')
                    ->action(function ($record) {
                        Artisan::call('set:clientify-deals', [
                            'leadId' => $record->id,
                        ]);
                    })
                    ->successNotificationTitle('Lead sincronizado correctamente')
                    // Cambia el color a gris si ya está sincronizado
                    ->color(fn ($record) => $record->in_clientify ? 'gray' : 'warning')
                    ->tooltip(fn ($record) => $record->in_clientify 
                        ? 'Ya sincronizado el ' . $record->synced_at?->format('d/m/Y H:i') 
                        : 'Sincronizar con Clientify'
                    ),
            ])
            ->headerActions([
                // headerAction para re-procesar errores:
                Action::make('reintentar_errores')
                    ->label('Reintentar errores')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn () => Lead::where('estado', 'error')->exists())
                    ->action(function () {
                        $leads = Lead::where('estado', 'error')->get();

                        $jobs = $leads->map(fn ($lead) => new ProcesarCVJob($lead))->toArray();

                        Bus::batch($jobs)
                            ->name('Reintento errores - ' . now()->format('d/m/Y H:i'))
                            ->allowFailures()
                            ->dispatch();
                    })
                    ->badge(fn () => Lead::where('estado', 'error')->count())
                    ->badgeColor('danger'),
            ]);
    }
}
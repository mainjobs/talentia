<?php

namespace App\Filament\Resources\Ofertas\Pages;

use App\Filament\Resources\Ofertas\OfertaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOfertas extends ListRecords
{
    protected static string $resource = OfertaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

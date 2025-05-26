<?php

namespace App\Filament\Resources\OccurrenceResource\Pages;

use App\Filament\Resources\OccurrenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOccurrences extends ListRecords
{
    protected static string $resource = OccurrenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

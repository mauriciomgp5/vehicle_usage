<?php

namespace App\Filament\Resources\OccurrenceResource\Pages;

use App\Filament\Resources\OccurrenceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOccurrence extends EditRecord
{
    protected static string $resource = OccurrenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

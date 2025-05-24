<?php

namespace App\Filament\Resources\VehicleUsageResource\Pages;

use App\Filament\Resources\VehicleUsageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleUsage extends EditRecord
{
    protected static string $resource = VehicleUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

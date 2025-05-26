<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        
        // Apenas supervisores podem criar novos usuários
        if (!$user->is_supervisor) {
            $this->halt();
        }
        
        parent::authorizeAccess();
    }
}

<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->is_supervisor && auth()->user()->id !== $this->record->id),
        ];
    }

    protected function authorizeAccess(): void
    {
        $user = auth()->user();
        
        // Se não for supervisor, só pode editar o próprio perfil
        if (!$user->is_supervisor && $user->id !== $this->record->id) {
            $this->halt();
        }
        
        parent::authorizeAccess();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remove a senha do formulário para não mostrar o hash
        unset($data['password']);
        
        return $data;
    }
}

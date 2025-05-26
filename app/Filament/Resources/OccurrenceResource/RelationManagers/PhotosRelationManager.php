<?php

namespace App\Filament\Resources\OccurrenceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';
    
    protected static ?string $title = 'Fotos da Ocorrência';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->label('Foto')
                    ->image()
                    ->directory('occurrence_photos')
                    ->visibility('public')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('caption')
                    ->label('Legenda')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_filename')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Foto')
                    ->size(60)
                    ->disk('public'),
                Tables\Columns\TextColumn::make('original_filename')
                    ->label('Nome do Arquivo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('caption')
                    ->label('Legenda')
                    ->limit(30)
                    ->placeholder('Sem legenda'),
                Tables\Columns\TextColumn::make('mime_type')
                    ->label('Tipo')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Tamanho')
                    ->sortable('size'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Adicionar Foto'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Visualizar')
                    ->modalContent(fn ($record) => view('filament.components.image-viewer', [
                        'imageUrl' => asset('storage/' . $record->path),
                        'caption' => $record->caption,
                        'filename' => $record->original_filename
                    ]))
                    ->modalWidth('lg'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Deletar o arquivo físico quando o registro for deletado
                        $mediaService = app(\App\Services\WhatsAppMediaService::class);
                        $mediaService->deletePhoto($record->path);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Deletar os arquivos físicos quando os registros forem deletados
                            $mediaService = app(\App\Services\WhatsAppMediaService::class);
                            foreach ($records as $record) {
                                $mediaService->deletePhoto($record->path);
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

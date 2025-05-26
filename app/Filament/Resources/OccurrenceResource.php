<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OccurrenceResource\Pages;
use App\Filament\Resources\OccurrenceResource\RelationManagers;
use App\Models\Occurrence;
use App\Models\VehicleUsage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OccurrenceResource extends Resource
{
    protected static ?string $model = Occurrence::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static ?string $navigationLabel = 'Ocorrências';
    
    protected static ?string $modelLabel = 'Ocorrência';
    
    protected static ?string $pluralModelLabel = 'Ocorrências';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Ocorrência')
                    ->schema([
                        Forms\Components\Select::make('vehicle_usage_id')
                            ->label('Uso do Veículo')
                            ->required()
                            ->relationship('vehicleUsage')
                            ->getOptionLabelFromRecordUsing(function (VehicleUsage $record): string {
                                return "{$record->vehicle->plate} - {$record->user->name} (" . 
                                       $record->checkout_at->format('d/m/Y H:i') . ")";
                            })
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->required()
                            ->options([
                                'incident' => 'Incidente',
                                'maintenance' => 'Manutenção',
                                'damage' => 'Dano',
                                'other' => 'Outros',
                            ])
                            ->default('incident'),
                        Forms\Components\Select::make('severity')
                            ->label('Gravidade')
                            ->required()
                            ->options([
                                'low' => 'Baixa',
                                'medium' => 'Média',
                                'high' => 'Alta',
                                'critical' => 'Crítica',
                            ])
                            ->default('low'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Localização (Opcional)')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->placeholder('-23.550520'),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->placeholder('-46.633308'),
                    ])->columns(2)->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicleUsage.vehicle.plate')
                    ->label('Veículo')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => 
                        "{$record->vehicleUsage->vehicle->plate} - {$record->vehicleUsage->vehicle->brand} {$record->vehicleUsage->vehicle->model}"
                    ),
                Tables\Columns\TextColumn::make('vehicleUsage.user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(50)
                    ->tooltip(function (Occurrence $record): ?string {
                        return $record->description;
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'incident' => 'warning',
                        'maintenance' => 'info',
                        'damage' => 'danger',
                        'other' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'incident' => 'Incidente',
                        'maintenance' => 'Manutenção',
                        'damage' => 'Dano',
                        'other' => 'Outros',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('severity')
                    ->label('Gravidade')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Baixa',
                        'medium' => 'Média',
                        'high' => 'Alta',
                        'critical' => 'Crítica',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('photos_count')
                    ->label('Fotos')
                    ->counts('photos')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'incident' => 'Incidente',
                        'maintenance' => 'Manutenção',
                        'damage' => 'Dano',
                        'other' => 'Outros',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Gravidade')
                    ->options([
                        'low' => 'Baixa',
                        'medium' => 'Média',
                        'high' => 'Alta',
                        'critical' => 'Crítica',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOccurrences::route('/'),
            'create' => Pages\CreateOccurrence::route('/create'),
            'edit' => Pages\EditOccurrence::route('/{record}/edit'),
        ];
    }
}

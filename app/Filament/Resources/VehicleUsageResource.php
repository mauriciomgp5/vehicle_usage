<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleUsageResource\Pages;
use App\Filament\Resources\VehicleUsageResource\RelationManagers;
use App\Models\VehicleUsage;
use App\Models\Vehicle;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class VehicleUsageResource extends Resource
{
    protected static ?string $model = VehicleUsage::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationLabel = 'Uso de Veículos';
    
    protected static ?string $modelLabel = 'Uso de Veículo';
    
    protected static ?string $pluralModelLabel = 'Uso de Veículos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Uso')
                    ->schema([
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Veículo')
                            ->required()
                            ->relationship('vehicle', 'plate')
                            ->getOptionLabelFromRecordUsing(fn (Vehicle $record): string => "{$record->plate} - {$record->brand} {$record->model}")
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->label('Usuário')
                    ->required()
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('purpose')
                            ->label('Finalidade')
                    ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Controle de Saída e Entrada')
                    ->schema([
                Forms\Components\DateTimePicker::make('checkout_at')
                            ->label('Data/Hora de Saída')
                            ->required()
                            ->native(false)
                            ->default(now()),
                        Forms\Components\DateTimePicker::make('checkin_at')
                            ->label('Data/Hora de Entrada')
                            ->native(false)
                            ->after('checkout_at'),
                Forms\Components\TextInput::make('initial_km')
                            ->label('Quilometragem Inicial')
                            ->numeric()
                            ->suffix('km')
                            ->minValue(0),
                Forms\Components\TextInput::make('final_km')
                            ->label('Quilometragem Final')
                            ->numeric()
                            ->suffix('km')
                            ->minValue(0)
                            ->gte('initial_km'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Observações')
                    ->schema([
                Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3)
                    ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.plate')
                    ->label('Veículo')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => "{$record->vehicle->plate} - {$record->vehicle->brand} {$record->vehicle->model}"),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('checkout_at')
                    ->label('Saída')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->checkin_at ? null : 'warning'),
                Tables\Columns\TextColumn::make('checkin_at')
                    ->label('Entrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Em uso')
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duração')
                    ->getStateUsing(function ($record) {
                        $start = $record->checkout_at;
                        $end = $record->checkin_at ?? now();
                        $diff = $start->diff($end);
                        
                        if ($diff->days > 0) {
                            return "{$diff->days}d {$diff->h}h {$diff->i}m";
                        } elseif ($diff->h > 0) {
                            return "{$diff->h}h {$diff->i}m";
                        } else {
                            return "{$diff->i}m";
                        }
                    })
                    ->color(function ($record) {
                        $hours = $record->checkout_at->diffInHours($record->checkin_at ?? now());
                        if ($hours > 24) return 'danger';
                        if ($hours > 8) return 'warning';
                        return 'success';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('initial_km')
                    ->label('KM Inicial')
                    ->numeric()
                    ->suffix(' km')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('final_km')
                    ->label('KM Final')
                    ->numeric()
                    ->suffix(' km')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('distance')
                    ->label('Distância')
                    ->getStateUsing(function ($record) {
                        if ($record->initial_km && $record->final_km) {
                            $distance = $record->final_km - $record->initial_km;
                            return number_format($distance, 0) . ' km';
                        }
                        return '-';
                    })
                    ->color(function ($record) {
                        if ($record->initial_km && $record->final_km) {
                            $distance = $record->final_km - $record->initial_km;
                            if ($distance > 500) return 'warning';
                            if ($distance > 200) return 'primary';
                        }
                        return null;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->label('Finalidade')
                    ->limit(30)
                    ->tooltip(function (VehicleUsage $record): ?string {
                        return $record->purpose;
                    })
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        if ($record->checkin_at) {
                            return 'finished';
                        }
                        $hours = $record->checkout_at->diffInHours(now());
                        if ($hours > 24) {
                            return 'overdue';
                        }
                        return 'in_use';
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'finished' => 'heroicon-o-check-circle',
                        'in_use' => 'heroicon-o-clock',
                        'overdue' => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'finished' => 'success',
                        'in_use' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        'finished' => 'Finalizado',
                        'in_use' => 'Em uso',
                        'overdue' => 'Atrasado (>24h)',
                        default => 'Status desconhecido',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Observações')
                    ->limit(20)
                    ->placeholder('Sem observações')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_id')
                    ->label('Veículo')
                    ->relationship('vehicle', 'plate')
                    ->getOptionLabelFromRecordUsing(fn (Vehicle $record): string => "{$record->plate} - {$record->brand} {$record->model}")
                    ->searchable()
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Usuário')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('vehicle_status')
                    ->label('Status do Veículo')
                    ->options([
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                        'maintenance' => 'Manutenção',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], function (Builder $query, $status) {
                            $query->whereHas('vehicle', function (Builder $query) use ($status) {
                                $query->where('status', $status);
                            });
                        });
                    }),

                Tables\Filters\SelectFilter::make('usage_status')
                    ->label('Status do Uso')
                    ->options([
                        'in_use' => 'Em Uso',
                        'finished' => 'Finalizado',
                        'overdue' => 'Atrasado (>24h)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], function (Builder $query, $status) {
                            match($status) {
                                'in_use' => $query->whereNull('checkin_at'),
                                'finished' => $query->whereNotNull('checkin_at'),
                                'overdue' => $query->whereNull('checkin_at')
                                    ->where('checkout_at', '<', now()->subDay()),
                                default => $query
                            };
                        });
                    }),

                Tables\Filters\Filter::make('checkout_period')
                    ->label('Período de Saída')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('De')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Até')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('checkout_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('checkout_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'De: ' . Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Até: ' . Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('distance_range')
                    ->label('Distância Percorrida')
                    ->form([
                        Forms\Components\TextInput::make('min_distance')
                            ->label('Distância Mínima (km)')
                            ->numeric()
                            ->suffix('km'),
                        Forms\Components\TextInput::make('max_distance')
                            ->label('Distância Máxima (km)')
                            ->numeric()
                            ->suffix('km'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_distance'],
                                fn (Builder $query, $distance): Builder => 
                                    $query->whereRaw('(final_km - initial_km) >= ?', [$distance])
                                          ->whereNotNull('final_km')
                                          ->whereNotNull('initial_km')
                            )
                            ->when(
                                $data['max_distance'],
                                fn (Builder $query, $distance): Builder => 
                                    $query->whereRaw('(final_km - initial_km) <= ?', [$distance])
                                          ->whereNotNull('final_km')
                                          ->whereNotNull('initial_km')
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_distance'] ?? null) {
                            $indicators['min_distance'] = 'Min: ' . $data['min_distance'] . 'km';
                        }
                        if ($data['max_distance'] ?? null) {
                            $indicators['max_distance'] = 'Max: ' . $data['max_distance'] . 'km';
                        }
                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('period_preset')
                    ->label('Período Rápido')
                    ->options([
                        'today' => 'Hoje',
                        'yesterday' => 'Ontem',
                        'this_week' => 'Esta Semana',
                        'last_week' => 'Semana Passada',
                        'this_month' => 'Este Mês',
                        'last_month' => 'Mês Passado',
                        'this_year' => 'Este Ano',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], function (Builder $query, $period) {
                            match($period) {
                                'today' => $query->whereDate('checkout_at', today()),
                                'yesterday' => $query->whereDate('checkout_at', yesterday()),
                                'this_week' => $query->whereBetween('checkout_at', [
                                    now()->startOfWeek(), 
                                    now()->endOfWeek()
                                ]),
                                'last_week' => $query->whereBetween('checkout_at', [
                                    now()->subWeek()->startOfWeek(), 
                                    now()->subWeek()->endOfWeek()
                                ]),
                                'this_month' => $query->whereBetween('checkout_at', [
                                    now()->startOfMonth(), 
                                    now()->endOfMonth()
                                ]),
                                'last_month' => $query->whereBetween('checkout_at', [
                                    now()->subMonth()->startOfMonth(), 
                                    now()->subMonth()->endOfMonth()
                                ]),
                                'this_year' => $query->whereBetween('checkout_at', [
                                    now()->startOfYear(), 
                                    now()->endOfYear()
                                ]),
                                default => $query
                            };
                        });
                    }),

                Tables\Filters\Filter::make('purpose_contains')
                    ->label('Finalidade Contém')
                    ->form([
                        Forms\Components\TextInput::make('purpose_search')
                            ->label('Buscar na Finalidade')
                            ->placeholder('Ex: reunião, entrega, viagem...')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['purpose_search'],
                            fn (Builder $query, $search): Builder => 
                                $query->where('purpose', 'like', "%{$search}%")
                        );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['purpose_search'] ?? null) {
                            $indicators['purpose_search'] = 'Finalidade: "' . $data['purpose_search'] . '"';
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('long_usage')
                    ->label('Uso Prolongado (>8h)')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('checkin_at')
                              ->whereRaw('TIMESTAMPDIFF(HOUR, checkout_at, checkin_at) > 8')
                    ),

                Tables\Filters\Filter::make('no_km_recorded')
                    ->label('Sem KM Registrada')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => 
                        $query->where(function ($query) {
                            $query->whereNull('initial_km')
                                  ->orWhereNull('final_km');
                        })
                    ),

                Tables\Filters\Filter::make('weekend_usage')
                    ->label('Uso em Fim de Semana')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => 
                        $query->whereRaw('DAYOFWEEK(checkout_at) IN (1, 7)')  // Domingo = 1, Sábado = 7
                    ),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('finish_usage')
                    ->label('Finalizar Uso')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (VehicleUsage $record): bool => !$record->checkin_at)
                    ->form([
                        Forms\Components\DateTimePicker::make('checkin_at')
                            ->label('Data/Hora de Entrada')
                            ->default(now())
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('final_km')
                            ->label('Quilometragem Final')
                            ->numeric()
                            ->suffix('km')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Observações Finais')
                            ->rows(2),
                    ])
                    ->action(function (array $data, VehicleUsage $record): void {
                        $record->update($data);
                        
                        // Atualizar KM do veículo
                        if ($data['final_km']) {
                            $record->vehicle->update(['km' => $data['final_km']]);
                        }
                    })
                    ->successNotificationTitle('Uso finalizado com sucesso!'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('Exportar Selecionados')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($records) {
                            // Aqui você pode implementar a lógica de exportação
                            return response()->streamDownload(function () use ($records) {
                                echo "Placa,Usuário,Saída,Entrada,KM Inicial,KM Final,Distância,Finalidade\n";
                                foreach ($records as $record) {
                                    $distance = ($record->initial_km && $record->final_km) 
                                        ? $record->final_km - $record->initial_km 
                                        : 0;
                                    echo implode(',', [
                                        $record->vehicle->plate,
                                        $record->user->name,
                                        $record->checkout_at->format('d/m/Y H:i'),
                                        $record->checkin_at?->format('d/m/Y H:i') ?? 'Em uso',
                                        $record->initial_km ?? 0,
                                        $record->final_km ?? 0,
                                        $distance,
                                        '"' . str_replace('"', '""', $record->purpose) . '"'
                                    ]) . "\n";
                                }
                            }, 'uso_veiculos_' . now()->format('Y-m-d_H-i-s') . '.csv');
                        }),
                ]),
            ])
            ->headerActions([
                // Removido o botão de estatísticas
            ])
            ->defaultSort('checkout_at', 'desc')
            ->striped()
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->poll('30s'); // Atualiza a cada 30 segundos para mostrar status em tempo real
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleUsages::route('/'),
            'create' => Pages\CreateVehicleUsage::route('/create'),
            'edit' => Pages\EditVehicleUsage::route('/{record}/edit'),
        ];
    }
}

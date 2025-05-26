<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopVehiclesWidget extends BaseWidget
{
    protected static ?string $heading = 'Top 5 Veículos Mais Utilizados Este Mês';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Vehicle::query()
                    ->withCount([
                        'usages as usages_this_month_count' => function (Builder $query) {
                            $query->whereBetween('checkout_at', [
                                now()->startOfMonth(),
                                now()->endOfMonth()
                            ]);
                        }
                    ])
                    ->having('usages_this_month_count', '>', 0)
                    ->orderBy('usages_this_month_count', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('plate')
                    ->label('Placa')
                    ->weight('bold')
                    ->icon('heroicon-m-identification'),
                    
                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->placeholder('Não informado'),
                    
                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->placeholder('Não informado'),
                    
                Tables\Columns\TextColumn::make('usages_this_month_count')
                    ->label('Usos Este Mês')
                    ->badge()
                    ->color(function ($state) {
                        if ($state >= 10) return 'danger';
                        if ($state >= 5) return 'warning';
                        return 'success';
                    })
                    ->suffix(' uso(s)'),
                    
                Tables\Columns\TextColumn::make('km')
                    ->label('KM Atual')
                    ->numeric()
                    ->suffix(' km'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'maintenance' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Ativo',
                        'inactive' => 'Inativo', 
                        'maintenance' => 'Manutenção',
                        default => $state,
                    }),
            ])
            ->defaultSort('usages_this_month_count', 'desc')
            ->paginated(false);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\VehicleUsage;
use App\Models\Vehicle;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class VehicleUsageStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        // Estatísticas de hoje
        $todayUsages = VehicleUsage::whereDate('checkout_at', today())->count();
        $todayActiveUsages = VehicleUsage::whereDate('checkout_at', today())
            ->whereNull('checkin_at')
            ->count();
        $todayFinishedUsages = VehicleUsage::whereDate('checkout_at', today())
            ->whereNotNull('checkin_at')
            ->count();
        
        // Estatísticas da semana
        $weekStart = now()->startOfWeek();
        $weekUsages = VehicleUsage::whereBetween('checkout_at', [$weekStart, now()])->count();
        $weekKm = VehicleUsage::whereBetween('checkout_at', [$weekStart, now()])
            ->whereNotNull('final_km')
            ->whereNotNull('initial_km')
            ->selectRaw('SUM(final_km - initial_km) as total_km')
            ->value('total_km') ?? 0;
        
        // Estatísticas do mês
        $monthStart = now()->startOfMonth();
        $monthUsages = VehicleUsage::whereBetween('checkout_at', [$monthStart, now()])->count();
        $monthActiveVehicles = Vehicle::whereHas('usages', function ($query) use ($monthStart) {
            $query->whereBetween('checkout_at', [$monthStart, now()]);
        })->count();
        
        // Estatísticas gerais
        $totalVehicles = Vehicle::where('status', 'ativo')->count();
        $totalUsers = User::where('is_active', true)->count();
        
        return [
            // Estatísticas de Hoje
            Stat::make('Usos Hoje', $todayUsages)
                ->description('Total de usos iniciados hoje')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20',
                ]),
                
            Stat::make('Em Uso Agora', $todayActiveUsages)
                ->description('Veículos em uso no momento')
                ->descriptionIcon('heroicon-m-clock')
                ->color($todayActiveUsages > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => $todayActiveUsages > 0 
                        ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20'
                        : 'bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                ]),
                
            Stat::make('Finalizados Hoje', $todayFinishedUsages)
                ->description('Usos concluídos hoje')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                ]),
                
            // Estatísticas da Semana
            Stat::make('Usos Esta Semana', $weekUsages)
                ->description('Total de usos na semana')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-cyan-50 to-cyan-100 dark:from-cyan-900/20 dark:to-cyan-800/20',
                ]),
                
            Stat::make('KM Esta Semana', number_format($weekKm, 0, ',', '.'))
                ->description('Quilometragem percorrida')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-cyan-50 to-cyan-100 dark:from-cyan-900/20 dark:to-cyan-800/20',
                ]),
                
            // Estatísticas do Mês
            Stat::make('Usos Este Mês', $monthUsages)
                ->description('Total de usos no mês')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('purple')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20',
                ]),
                
            Stat::make('Veículos Ativos', $monthActiveVehicles . '/' . $totalVehicles)
                ->description('Veículos usados este mês')
                ->descriptionIcon('heroicon-m-truck')
                ->color('purple')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20',
                ]),
                
            // Estatísticas Gerais
            Stat::make('Usuários Ativos', $totalUsers)
                ->description('Total de usuários habilitados')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20',
                ]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 4; // 4 colunas para layout responsivo
    }
}

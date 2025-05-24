<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use App\Models\VehicleUsage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class AlertsWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected function getHeading(): string
    {
        return 'Alertas e Atenções';
    }
    
    protected function getStats(): array
    {
        // Veículos com licenciamento vencido
        $expiredLicensing = Vehicle::where('licensing_due_date', '<', today())
            ->where('status', 'ativo')
            ->count();
            
        // Veículos próximos do vencimento (próximos 30 dias)
        $soonToExpire = Vehicle::whereBetween('licensing_due_date', [
            today(),
            today()->addDays(30)
        ])
        ->where('status', 'ativo')
        ->count();
        
        // Veículos em manutenção
        $inMaintenance = Vehicle::where('status', 'manutencao')->count();
        
        // Usos prolongados (mais de 24 horas sem devolução)
        $prolongedUsages = VehicleUsage::whereNull('checkin_at')
            ->where('checkout_at', '<', now()->subDay())
            ->count();
            
        // Usos sem quilometragem final registrada (finalizados mas sem KM)
        $usagesWithoutKm = VehicleUsage::whereNotNull('checkin_at')
            ->whereNull('final_km')
            ->whereDate('checkin_at', '>=', today()->subDays(7))
            ->count();

        return [
            Stat::make('Licenciamentos Vencidos', $expiredLicensing)
                ->description('Veículos com licenciamento vencido')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($expiredLicensing > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => $expiredLicensing > 0 
                        ? 'bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20'
                        : 'bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                ]),
                
            Stat::make('Vencimento Próximo', $soonToExpire)
                ->description('Vencem em até 30 dias')
                ->descriptionIcon('heroicon-m-clock')
                ->color($soonToExpire > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => $soonToExpire > 0 
                        ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20'
                        : 'bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                ]),
                
            Stat::make('Em Manutenção', $inMaintenance)
                ->description('Veículos indisponíveis')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color($inMaintenance > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => $inMaintenance > 0 
                        ? 'bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20'
                        : 'bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                ]),
                
            Stat::make('Usos Prolongados', $prolongedUsages)
                ->description('Mais de 24h sem devolução')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($prolongedUsages > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => $prolongedUsages > 0 
                        ? 'bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20'
                        : 'bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                ]),
                
            Stat::make('Sem KM Final', $usagesWithoutKm)
                ->description('Últimos 7 dias sem KM')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color($usagesWithoutKm > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => $usagesWithoutKm > 0 
                        ? 'bg-gradient-to-r from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20'
                        : 'bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                ]),
        ];
    }
    
    protected function getColumns(): int
    {
        return 5;
    }
}

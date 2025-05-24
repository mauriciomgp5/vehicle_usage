<?php

namespace App\Filament\Widgets;

use App\Models\VehicleUsage;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class VehicleUsageChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Uso de Veículos - Últimos 7 Dias';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels = [];
        $usageData = [];
        $finishedData = [];
        
        // Dados dos últimos 7 dias
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');
            
            // Total de usos iniciados no dia
            $totalUsages = VehicleUsage::whereDate('checkout_at', $date)->count();
            $usageData[] = $totalUsages;
            
            // Total de usos finalizados no dia
            $finishedUsages = VehicleUsage::whereDate('checkin_at', $date)->count();
            $finishedData[] = $finishedUsages;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Usos Iniciados',
                    'data' => $usageData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Usos Finalizados',
                    'data' => $finishedData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}

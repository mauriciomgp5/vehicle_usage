<?php

namespace App\Services;

use App\Models\UserSession;
use Illuminate\Support\Facades\Log;

class MenuService
{
    private $menus = [
        'main_menu' => [
            'message' => "Bem-vindo! Escolha uma opção:\n\n" .
                        "1 - Utilizar Veículo\n" .
                        "2 - Abastecimento\n" .
                        "3 - Manutenção\n" .
                        "4 - Relatórios\n" .
                        "5 - Suporte",
            'options' => [
                '1' => 'vehicle_usage',
                '2' => 'fuel',
                '3' => 'maintenance',
                '4' => 'reports',
                '5' => 'support'
            ]
        ],
        'vehicle_usage' => [
            'message' => "Utilização de Veículo:\n\n" .
                        "1 - Registrar Saída\n" .
                        "2 - Registrar Retorno\n" .
                        "3 - Consultar Status\n" .
                        "0 - Voltar ao Menu Principal",
            'options' => [
                '1' => 'register_departure',
                '2' => 'register_return',
                '3' => 'check_status',
                '0' => 'main_menu'
            ]
        ],
        'fuel' => [
            'message' => "Abastecimento:\n\n" .
                        "1 - Registrar Abastecimento\n" .
                        "2 - Consultar Histórico\n" .
                        "3 - Relatório de Consumo\n" .
                        "0 - Voltar ao Menu Principal",
            'options' => [
                '1' => 'register_fuel',
                '2' => 'fuel_history',
                '3' => 'fuel_report',
                '0' => 'main_menu'
            ]
        ]
    ];

    public function getMenuMessage(string $menuName): string
    {
        return $this->menus[$menuName]['message'] ?? $this->menus['main_menu']['message'];
    }

    public function processOption(string $currentMenu, string $option): string
    {
        if (!isset($this->menus[$currentMenu])) {
            return $this->menus['main_menu']['message'];
        }

        $nextMenu = $this->menus[$currentMenu]['options'][$option] ?? 'main_menu';
        return $this->getMenuMessage($nextMenu);
    }

    public function handleUserResponse(string $phone, string $message): array
    {
        $session = UserSession::firstOrCreate(
            ['phone' => $phone],
            ['current_menu' => 'main_menu', 'last_interaction' => now()]
        );

        $currentMenu = $session->current_menu;
        $response = $this->processOption($currentMenu, $message);

        // Atualiza a sessão
        $session->update([
            'current_menu' => $this->menus[$currentMenu]['options'][$message] ?? 'main_menu',
            'last_interaction' => now()
        ]);

        return [
            'message' => $response,
            'menu' => $session->current_menu
        ];
    }
} 
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MenuService
{
    protected $redisSessionService;
    private $menus = [
        'main_menu' => [
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
        'register_departure' => [
            'message' => "Por favor, informe a placa do veículo:",
            'options' => []
        ],
        'register_return' => [
            'message' => "Por favor, informe a placa do veículo:",
            'options' => []
        ],
        'check_status' => [
            'message' => "Por favor, informe a placa do veículo:",
            'options' => []
        ]
    ];

    public function __construct(RedisSessionService $redisSessionService)
    {
        $this->redisSessionService = $redisSessionService;
    }

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
        $currentMenu = $this->redisSessionService->getCurrentMenu($phone);
        $response = $this->processOption($currentMenu, $message);

        // Atualiza a sessão no Redis
        $nextMenu = $this->menus[$currentMenu]['options'][$message] ?? 'main_menu';
        $this->redisSessionService->updateMenu($phone, $nextMenu);

        return [
            'message' => $response,
            'menu' => $nextMenu
        ];
    }
} 
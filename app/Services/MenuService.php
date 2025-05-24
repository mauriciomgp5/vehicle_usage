<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Vehicle;

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
        ],
        'select_vehicle' => [
            'message' => "Selecione o número do veículo desejado:",
            'options' => []
        ],
        'ask_km' => [
            'message' => "Por favor, informe o KM inicial do veículo:",
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

        // Fluxo especial para busca de veículos
        if (in_array($currentMenu, ['register_departure', 'register_return', 'check_status'])) {
            $veiculos = Vehicle::where('plate', 'like', "%{$message}%")->get();
            if ($veiculos->count() === 0) {
                return [
                    'message' => "Nenhum veículo encontrado com essa placa. Por favor, tente novamente:",
                    'menu' => $currentMenu
                ];
            } elseif ($veiculos->count() === 1) {
                $veiculo = $veiculos->first();
                $this->redisSessionService->setSessionData($phone, 'vehicle_id', $veiculo->id);
                $this->redisSessionService->updateMenu($phone, 'ask_km');
                return [
                    'message' => "Veículo encontrado: {$veiculo->brand} {$veiculo->model} ({$veiculo->plate})\nPor favor, informe o KM inicial do veículo:",
                    'menu' => 'ask_km'
                ];
            } else {
                // Mais de um veículo encontrado
                $lista = "Foram encontrados mais de um veículo:\n";
                foreach ($veiculos as $idx => $v) {
                    $lista .= ($idx+1) . " - {$v->brand} {$v->model} ({$v->plate})\n";
                }
                $this->redisSessionService->setSessionData($phone, 'vehicle_options', $veiculos->pluck('id')->toArray());
                $this->redisSessionService->updateMenu($phone, 'select_vehicle');
                return [
                    'message' => $lista . "\nResponda com o número do veículo desejado:",
                    'menu' => 'select_vehicle'
                ];
            }
        }

        // Seleção de veículo após múltiplos resultados
        if ($currentMenu === 'select_vehicle') {
            $options = $this->redisSessionService->getSessionData($phone, 'vehicle_options', []);
            $idx = intval($message) - 1;
            if (isset($options[$idx])) {
                $vehicleId = $options[$idx];
                $veiculo = Vehicle::find($vehicleId);
                $this->redisSessionService->setSessionData($phone, 'vehicle_id', $veiculo->id);
                $this->redisSessionService->updateMenu($phone, 'ask_km');
                return [
                    'message' => "Veículo selecionado: {$veiculo->brand} {$veiculo->model} ({$veiculo->plate})\nPor favor, informe o KM inicial do veículo:",
                    'menu' => 'ask_km'
                ];
            } else {
                return [
                    'message' => "Opção inválida. Por favor, responda com o número do veículo desejado:",
                    'menu' => 'select_vehicle'
                ];
            }
        }

        // Se está pedindo o KM, apenas confirma e volta ao menu principal
        if ($currentMenu === 'ask_km') {
            $km = $message;
            $vehicleId = $this->redisSessionService->getSessionData($phone, 'vehicle_id');
            $veiculo = Vehicle::find($vehicleId);
            // Aqui você pode salvar o uso do veículo, se desejar
            $this->redisSessionService->updateMenu($phone, 'main_menu');
            return [
                'message' => "Saída registrada para o veículo {$veiculo->brand} {$veiculo->model} ({$veiculo->plate}) com KM inicial: {$km}\n\n" . $this->getMenuMessage('main_menu'),
                'menu' => 'main_menu'
            ];
        }

        // Fluxo padrão
        $response = $this->processOption($currentMenu, $message);
        $nextMenu = $this->menus[$currentMenu]['options'][$message] ?? 'main_menu';
        $this->redisSessionService->updateMenu($phone, $nextMenu);
        return [
            'message' => $response,
            'menu' => $nextMenu
        ];
    }
} 
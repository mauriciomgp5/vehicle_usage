<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Vehicle;
use App\Models\VehicleUsage;
use App\Models\User;

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
        ],
        'active_usage_menu' => [
            'message' => "Você possui um veículo em uso. O que deseja fazer?\n1 - Devolver veículo\n2 - Registrar ocorrência",
            'options' => [
                '1' => 'return_km',
                '2' => 'register_ocorrencia'
            ]
        ],
        'return_km' => [
            'message' => "Por favor, informe o KM final do veículo:",
            'options' => []
        ],
        'register_ocorrencia' => [
            'message' => "Por favor, descreva a ocorrência:",
            'options' => []
        ],
        'ask_name' => [
            'message' => "👋 Olá! Para continuar, por favor informe seu nome completo:\n\n(Ex: João da Silva)",
            'options' => []
        ],
        'confirm_checkout' => [
            'message' => "Confirme os dados para registrar a saída:",
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

    public function getOrCreateUser($phone, $session = null)
    {
        $user = User::where('phone', $phone)->first();
        if ($user) {
            return $user;
        }
        return null;
    }

    public function checkActiveUsage($userId)
    {
        return VehicleUsage::where('user_id', $userId)->whereNull('checkin_at')->first();
    }

    public function processOption(string $currentMenu, string $option): string
    {
        if (!isset($this->menus[$currentMenu])) {
            return $this->menus['main_menu']['message'];
        }

        $nextMenu = $this->menus[$currentMenu]['options'][$option] ?? 'main_menu';
        return $this->getMenuMessage($nextMenu);
    }

    public function cleanPhone($phone)
    {
        return preg_replace('/@.*$/', '', $phone);
    }

    private function notifySupervisors($message)
    {
        $supervisors = User::where('is_supervisor', true)->get();
        
        foreach ($supervisors as $supervisor) {
            try {
                // Aqui vamos usar o EvolutionApiService
                app('App\Services\EvolutionApiService')->sendMessage(
                    $supervisor->phone . '@s.whatsapp.net',
                    "🚨 MONITORAMENTO DE FROTA 🚨\n\n" . $message
                );
                
                Log::info('Notificação enviada para supervisor:', [
                    'supervisor' => $supervisor->name,
                    'phone' => $supervisor->phone,
                    'message' => $message
                ]);
            } catch (\Exception $e) {
                Log::error('Erro ao notificar supervisor:', [
                    'supervisor' => $supervisor->name,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function handleUserResponse(string $phone, string $message): array
    {
        $cleanPhone = $this->cleanPhone($phone);
        
        // Comando especial para limpar sessão
        if (trim(strtolower($message)) === '/clear') {
            $this->redisSessionService->deleteSession($phone);
            $this->redisSessionService->updateMenu($phone, 'main_menu');
            return [
                'message' => "🧹 Sessão limpa com sucesso!\n\n" . $this->getMenuMessage('main_menu'),
                'menu' => 'main_menu'
            ];
        }
        
        $currentMenu = $this->redisSessionService->getCurrentMenu($phone);
        $session = $this->redisSessionService->getSession($phone);

        // Se está aguardando o nome, criar usuário e seguir
        if ($currentMenu === 'ask_name') {
            // Validação básica do nome
            $name = trim($message);
            if (strlen($name) < 2) {
                return [
                    'message' => "Por favor, informe um nome válido com pelo menos 2 caracteres:",
                    'menu' => 'ask_name'
                ];
            }
            
            // Cria o usuário com o nome informado
            $user = User::create([
                'name' => $name,
                'phone' => $cleanPhone,
                'email' => $cleanPhone . '@fake.com',
                'password' => bcrypt('senha_padrao'),
            ]);
            $this->redisSessionService->deleteSession($phone); // Limpa sessão para novo fluxo
            $this->redisSessionService->updateMenu($phone, 'main_menu');
            return [
                'message' => "✅ Cadastro realizado com sucesso, {$user->name}!\n\nAgora você pode utilizar nosso sistema.",
                'menu' => 'main_menu',
                'send_menu_next' => true
            ];
        }

        // Sempre buscar usuário antes de qualquer ação
        $user = $this->getOrCreateUser($cleanPhone, $session);
        if (!$user) {
            // Se não existe, pedir o nome (SEM salvar a mensagem atual)
            $this->redisSessionService->updateMenu($phone, 'ask_name');
            return [
                'message' => $this->getMenuMessage('ask_name'),
                'menu' => 'ask_name'
            ];
        }
        $userId = $user->id;

        // Checagem de uso ativo ao iniciar conversa
        if ($currentMenu === 'main_menu') {
            $active = $this->checkActiveUsage($userId);
            if ($active) {
                $this->redisSessionService->updateMenu($phone, 'active_usage_menu');
                return [
                    'message' => $this->getMenuMessage('active_usage_menu'),
                    'menu' => 'active_usage_menu'
                ];
            }
        }

        // Fluxo especial para busca de veículos
        if (in_array($currentMenu, ['register_departure', 'register_return', 'check_status'])) {
            $veiculos = Vehicle::where('plate', 'like', "%{$message}%")->get();
            if ($veiculos->count() === 0) {
                return [
                    'message' => "❌ Nenhum veículo encontrado com essa placa.\n\n" .
                               "💡 Dicas:\n" .
                               "• Digite parte da placa (ex: ABC, 1234, DEF1234)\n" .
                               "• Verifique se a placa está correta\n\n" .
                               "Por favor, tente novamente:",
                    'menu' => $currentMenu
                ];
            } elseif ($veiculos->count() === 1) {
                $veiculo = $veiculos->first();
                $this->redisSessionService->setSessionData($phone, 'vehicle_id', $veiculo->id);
                $this->redisSessionService->updateMenu($phone, 'ask_km');
                return [
                    'message' => "✅ Veículo encontrado: {$veiculo->brand} {$veiculo->model} ({$veiculo->plate})\n\nPor favor, informe o KM inicial do veículo:",
                    'menu' => 'ask_km'
                ];
            } else {
                // Mais de um veículo encontrado
                $lista = "🚗 Foram encontrados " . $veiculos->count() . " veículos:\n\n";
                foreach ($veiculos as $idx => $v) {
                    $lista .= ($idx+1) . " - {$v->brand} {$v->model} ({$v->plate})\n";
                }
                $this->redisSessionService->setSessionData($phone, 'vehicle_options', $veiculos->pluck('id')->toArray());
                $this->redisSessionService->updateMenu($phone, 'select_vehicle');
                return [
                    'message' => $lista . "\n📝 Responda com o número do veículo desejado:",
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
                    'message' => "✅ Veículo selecionado: {$veiculo->brand} {$veiculo->model} ({$veiculo->plate})\n\nPor favor, informe o KM inicial do veículo:",
                    'menu' => 'ask_km'
                ];
            } else {
                return [
                    'message' => "❌ Opção inválida.\n\nPor favor, responda com o número do veículo desejado (1, 2, 3...):",
                    'menu' => 'select_vehicle'
                ];
            }
        }

        // Se está pedindo o KM, mostra confirmação antes de registrar
        if ($currentMenu === 'ask_km') {
            Log::info('DEBUG: Entrando no ask_km com message: ' . $message);
            $km = floatval($message);
            $vehicleId = $this->redisSessionService->getSessionData($phone, 'vehicle_id');
            $veiculo = Vehicle::find($vehicleId);
            
            // Validação: verificar último KM final do veículo
            $ultimoUso = VehicleUsage::where('vehicle_id', $vehicleId)
                                    ->whereNotNull('final_km')
                                    ->orderBy('checkin_at', 'desc')
                                    ->first();
            
            if ($ultimoUso && $km < $ultimoUso->final_km) {
                return [
                    'message' => "❌ Erro: KM inicial ({$km}) não pode ser menor que o último KM final registrado ({$ultimoUso->final_km}).\n\n" .
                               "Por favor, informe um KM inicial válido:",
                    'menu' => 'ask_km'
                ];
            }
            
            // Salva o KM para usar na confirmação
            $this->redisSessionService->setSessionData($phone, 'initial_km', $km);
            $this->redisSessionService->updateMenu($phone, 'confirm_checkout');
            
            Log::info('DEBUG: Mudando menu para confirm_checkout');
            
            return [
                'message' => "📋 Confirme os dados para registrar a saída:\n\n" .
                           "🚗 Veículo: {$veiculo->brand} {$veiculo->model} ({$veiculo->plate})\n" .
                           "📊 KM inicial: {$km}\n" .
                           "🕐 Data/Hora: " . now()->format('d/m/Y H:i') . "\n\n" .
                           "1 - ✅ CONFIRMAR\n" .
                           "0 - ❌ CANCELAR",
                'menu' => 'confirm_checkout'
            ];
        }

        // Confirmação da saída
        if ($currentMenu === 'confirm_checkout') {
            $response = trim($message);
            
            if ($response === '1') {
                // Confirma - registra a saída
                $vehicleId = $this->redisSessionService->getSessionData($phone, 'vehicle_id');
                $km = $this->redisSessionService->getSessionData($phone, 'initial_km');
                $veiculo = Vehicle::find($vehicleId);
                
                VehicleUsage::create([
                    'user_id' => $userId,
                    'vehicle_id' => $veiculo->id,
                    'initial_km' => $km,
                    'checkout_at' => now(),
                    'purpose' => '',
                ]);
                
                // Notificar supervisores sobre a saída
                $user = User::find($userId);
                $this->notifySupervisors(
                    "🚗 SAÍDA DE VEÍCULO\n\n" .
                    "👤 Usuário: {$user->name}\n" .
                    "📱 Telefone: {$user->phone}\n" .
                    "🚙 Veículo: {$veiculo->brand} {$veiculo->model}\n" .
                    "🏷️ Placa: {$veiculo->plate}\n" .
                    "📊 KM inicial: {$km}\n" .
                    "🕐 Horário: " . now()->format('d/m/Y H:i')
                );
                
                $this->redisSessionService->deleteSession($phone);
                return [
                    'message' => "✅ Saída registrada com sucesso!\n\n" .
                               "🚗 Veículo: {$veiculo->brand} {$veiculo->model} ({$veiculo->plate})\n" .
                               "📊 KM inicial: {$km}\n" .
                               "🕐 Horário: " . now()->format('d/m/Y H:i') . "\n\n" .
                               "Boa viagem! 🛣️",
                    'menu' => 'none'
                ];
            } elseif ($response === '0') {
                // Cancela - volta ao menu principal
                $this->redisSessionService->deleteSession($phone);
                return [
                    'message' => "❌ Operação cancelada.\n\n" . $this->getMenuMessage('main_menu'),
                    'menu' => 'main_menu'
                ];
            } else {
                // Resposta inválida
                return [
                    'message' => "❓ Resposta inválida.\n\n" .
                               "Por favor, digite:\n" .
                               "1 - ✅ CONFIRMAR\n" .
                               "0 - ❌ CANCELAR",
                    'menu' => 'confirm_checkout'
                ];
            }
        }

        // Menu de uso ativo: devolução ou ocorrência
        if ($currentMenu === 'active_usage_menu') {
            if ($message == '1') {
                $this->redisSessionService->updateMenu($phone, 'return_km');
                return [
                    'message' => $this->getMenuMessage('return_km'),
                    'menu' => 'return_km'
                ];
            } elseif ($message == '2') {
                $this->redisSessionService->updateMenu($phone, 'register_ocorrencia');
                return [
                    'message' => $this->getMenuMessage('register_ocorrencia'),
                    'menu' => 'register_ocorrencia'
                ];
            } else {
                return [
                    'message' => "Opção inválida.\n" . $this->getMenuMessage('active_usage_menu'),
                    'menu' => 'active_usage_menu'
                ];
            }
        }

        // Devolução: pede KM final, finaliza registro e encerra sessão
        if ($currentMenu === 'return_km') {
            $active = $this->checkActiveUsage($userId);
            if ($active) {
                $kmFinal = floatval($message);
                $kmInicial = floatval($active->initial_km);
                
                // Validação: KM final não pode ser menor que inicial
                if ($kmFinal < $kmInicial) {
                    return [
                        'message' => "❌ Erro: KM final ({$kmFinal}) não pode ser menor que o KM inicial ({$kmInicial}).\n\n" .
                                   "Por favor, informe um KM final válido:",
                        'menu' => 'return_km'
                    ];
                }
                
                $active->final_km = $kmFinal;
                $active->checkin_at = now();
                $active->save();
                
                // Notificar supervisores sobre o retorno
                $this->notifySupervisors(
                    "🏁 RETORNO DE VEÍCULO\n\n" .
                    "👤 Usuário: {$active->user->name}\n" .
                    "📱 Telefone: {$active->user->phone}\n" .
                    "🚙 Veículo: {$active->vehicle->brand} {$active->vehicle->model}\n" .
                    "🏷️ Placa: {$active->vehicle->plate}\n" .
                    "📊 KM final: {$kmFinal}\n" .
                    "📏 Distância: " . ($kmFinal - $kmInicial) . " km\n" .
                    "🕐 Horário: " . now()->format('d/m/Y H:i')
                );
            }
            $this->redisSessionService->deleteSession($phone);
            return [
                'message' => "✅ Devolução registrada com sucesso!\n\n" .
                           "🚗 Veículo: {$active->vehicle->brand} {$active->vehicle->model} ({$active->vehicle->plate})\n" .
                           "📊 KM final: {$kmFinal}\n" .
                           "📏 Distância percorrida: " . ($kmFinal - $kmInicial) . " km\n" .
                           "🕐 Horário: " . now()->format('d/m/Y H:i') . "\n\n" .
                           "Obrigado! 🙏",
                'menu' => 'none'
            ];
        }

        // Ocorrência: salva mensagem, encerra sessão
        if ($currentMenu === 'register_ocorrencia') {
            $active = $this->checkActiveUsage($userId);
            if ($active) {
                $ocorrencia = trim($message);
                
                // Validação mínima da ocorrência
                if (strlen($ocorrencia) < 10) {
                    return [
                        'message' => "❌ Descrição muito curta.\n\n" .
                                   "Por favor, descreva a ocorrência com mais detalhes (mínimo 10 caracteres):",
                        'menu' => 'register_ocorrencia'
                    ];
                }
                
                // Adiciona timestamp à ocorrência existente
                $dataAtual = now()->format('d/m/Y H:i');
                $novaOcorrencia = "[{$dataAtual}] {$ocorrencia}";
                
                if ($active->notes) {
                    $active->notes = $active->notes . "\n\n" . $novaOcorrencia;
                } else {
                    $active->notes = $novaOcorrencia;
                }
                
                $active->save();
                
                // Notificar supervisores sobre a ocorrência
                $this->notifySupervisors(
                    "⚠️ OCORRÊNCIA REGISTRADA\n\n" .
                    "👤 Usuário: {$active->user->name}\n" .
                    "📱 Telefone: {$active->user->phone}\n" .
                    "🚙 Veículo: {$active->vehicle->brand} {$active->vehicle->model}\n" .
                    "🏷️ Placa: {$active->vehicle->plate}\n" .
                    "📝 Ocorrência: {$ocorrencia}\n" .
                    "🕐 Horário: " . now()->format('d/m/Y H:i')
                );
            }
            $this->redisSessionService->deleteSession($phone);
            return [
                'message' => "✅ Ocorrência registrada com sucesso!\n\n" .
                           "📝 Descrição: {$ocorrencia}\n" .
                           "🚗 Veículo: {$active->vehicle->brand} {$active->vehicle->model} ({$active->vehicle->plate})\n" .
                           "🕐 Data/Hora: " . now()->format('d/m/Y H:i') . "\n\n" .
                           "Obrigado pelo registro! 📋",
                'menu' => 'none'
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
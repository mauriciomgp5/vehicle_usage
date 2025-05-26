<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Vehicle;
use App\Models\VehicleUsage;
use App\Models\User;
use App\Models\Occurrence;
use App\Models\OccurrencePhoto;

class MenuService
{
    protected $redisSessionService;
    private $menus = [
        'main_menu' => [
            'message' => "🚗 SISTEMA DE GESTÃO DE FROTA\n\n" .
                        "📋 Menu Principal:\n\n" .
                        "1️⃣ - Pegar Veículo\n" .
                        "2️⃣ - Devolver Veículo\n" .
                        "3️⃣ - Consultar Status\n" .
                        "0️⃣ - Voltar ao Menu Principal\n\n" .
                        "❌ Para limpar sessão, digite /clear",
            'options' => [
                '1' => 'register_departure',
                '2' => 'register_return',
                '3' => 'check_status',
                '0' => 'main_menu'
            ]
        ],
        'register_departure' => [
            'message' => "🚗 RETIRADA DE VEÍCULO\n\n" .
                        "📝 Por favor, informe a placa do veículo:\n\n" .
                        "💡 Dicas:\n" .
                        "• Digite parte da placa (ex: ABC, 1234)\n" .
                        "• Verifique se a placa está correta\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => []
        ],
        'register_return' => [
            'message' => "🚗 DEVOLUÇÃO DE VEÍCULO\n\n" .
                        "📝 Por favor, informe a placa do veículo:\n\n" .
                        "💡 Dicas:\n" .
                        "• Digite parte da placa (ex: ABC, 1234)\n" .
                        "• Verifique se a placa está correta\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => []
        ],
        'check_status' => [
            'message' => "🔍 CONSULTA DE STATUS\n\n" .
                        "📝 Por favor, informe a placa do veículo:\n\n" .
                        "💡 Dicas:\n" .
                        "• Digite parte da placa (ex: ABC, 1234)\n" .
                        "• Verifique se a placa está correta\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => []
        ],
        'select_vehicle' => [
            'message' => "🚗 SELEÇÃO DE VEÍCULO\n\n" .
                        "📋 Selecione o número do veículo desejado:\n\n" .
                        "💡 Dica: Digite apenas o número correspondente ao veículo\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => []
        ],
        'ask_km' => [
            'message' => "🚗 REGISTRO DE QUILOMETRAGEM\n\n" .
                        "📊 Por favor, informe o KM do veículo:\n\n" .
                        "💡 Dicas:\n" .
                        "• Verifique o painel do veículo\n" .
                        "• Digite apenas números\n" .
                        "• Use ponto para decimais (ex: 12345.6)\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => []
        ],
        'active_usage_menu' => [
            'message' => "🚗 VEÍCULO EM USO\n\n" .
                        "📋 Opções disponíveis:\n\n" .
                        "1️⃣ - Devolver veículo\n" .
                        "2️⃣ - Registrar ocorrência\n\n" .
                        "💡 Dica: Se houver algum problema com o veículo, registre uma ocorrência antes de devolvê-lo.\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => [
                '1' => 'return_km',
                '2' => 'register_ocorrencia'
            ]
        ],
        'return_km' => [
            'message' => "🚗 DEVOLUÇÃO DE VEÍCULO\n\n" .
                        "📊 Por favor, informe o KM final do veículo:\n\n" .
                        "💡 Dicas:\n" .
                        "• Verifique o painel do veículo\n" .
                        "• Digite apenas números\n" .
                        "• Use ponto para decimais (ex: 12345.6)\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => []
        ],
        'register_ocorrencia' => [
            'message' => "⚠️ REGISTRO DE OCORRÊNCIA\n\n" .
                        "📝 Por favor, descreva brevemente a ocorrência:\n\n" .
                        "💡 Dicas:\n" .
                        "• Seja claro e objetivo\n" .
                        "• Exemplos: 'Pneu furou', 'Arranhão lateral'\n" .
                        "• Descreva o problema com detalhes\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => []
        ],
        'register_ocorrencia_photos' => [
            'message' => "📸 REGISTRO DE FOTOS\n\n" .
                        "📋 Opções disponíveis:\n\n" .
                        "1️⃣ - Finalizar registro\n" .
                        "2️⃣ - Cancelar ocorrência\n\n" .
                        "💡 Dicas:\n" .
                        "• Envie fotos da ocorrência (uma por vez)\n" .
                        "• Adicione uma legenda para cada foto\n" .
                        "• Envie quantas fotos forem necessárias\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => [
                '1' => 'finalize_occurrence',
                '2' => 'cancel_occurrence'
            ]
        ],
        'ask_name' => [
            'message' => "👋 BEM-VINDO AO SISTEMA\n\n" .
                        "📝 Por favor, informe seu nome completo:\n\n" .
                        "💡 Dicas:\n" .
                        "• Digite seu nome completo\n" .
                        "• Exemplo: João da Silva\n" .
                        "• Mínimo 2 caracteres\n\n" .
                        "❌ Para cancelar, digite /clear",
            'options' => []
        ],
        'confirm_checkout' => [
            'message' => "✅ CONFIRMAÇÃO DE SAÍDA\n\n" .
                        "📋 Confirme os dados para registrar a saída:\n\n" .
                        "1️⃣ - CONFIRMAR\n" .
                        "0️⃣ - CANCELAR\n\n" .
                        "❌ Para cancelar, digite /clear",
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

    public function handleUserResponse(string $phone, string $message, string $messageType = 'text', ?string $mediaId = null, ?string $caption = null, ?array $imageData = null): array
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
                'message' => "✅ DEVOLUÇÃO REGISTRADA COM SUCESSO!\n\n" .
                           "🚗 Veículo: {$active->vehicle->brand} {$active->vehicle->model}\n" .
                           "🏷️ Placa: {$active->vehicle->plate}\n" .
                           "📊 KM inicial: {$kmInicial}\n" .
                           "📊 KM final: {$kmFinal}\n" .
                           "📏 Distância percorrida: " . ($kmFinal - $kmInicial) . " km\n" .
                           "⏱️ Tempo de uso: " . $active->checkout_at->diffForHumans($active->checkin_at) . "\n" .
                           "🕐 Data/Hora: " . now()->format('d/m/Y H:i') . "\n\n" .
                           "Obrigado por utilizar nosso sistema! 🙏\n" .
                           "Voltando ao menu principal...",
                'menu' => 'main_menu',
                'send_menu_next' => true
            ];
        }

        // Ocorrência: salva mensagem/foto, encerra sessão
        if ($currentMenu === 'register_ocorrencia') {
            $active = $this->checkActiveUsage($userId);
            if ($active) {
                $description = trim($message);
                
                // Validação mínima da descrição
                if (strlen($description) < 5) {
                    return [
                        'message' => "❌ Descrição muito curta.\n\n" .
                                   "Por favor, descreva a ocorrência com mais detalhes (mínimo 5 caracteres):",
                        'menu' => 'register_ocorrencia'
                    ];
                }
                
                // Criar a ocorrência inicial (sem fotos ainda)
                $occurrence = Occurrence::create([
                    'vehicle_usage_id' => $active->id,
                    'description' => $description,
                    'type' => 'incident',
                    'severity' => 'medium'
                ]);
                
                // Salvar ID da ocorrência na sessão para adicionar fotos
                $this->redisSessionService->setSessionData($phone, 'occurrence_id', $occurrence->id);
                $this->redisSessionService->setSessionData($phone, 'photos_count', 0);
                $this->redisSessionService->updateMenu($phone, 'register_ocorrencia_photos');
                
                return [
                    'message' => "✅ Ocorrência criada!\n\n" .
                               "📝 Descrição: {$description}\n\n" .
                               $this->getMenuMessage('register_ocorrencia_photos'),
                    'menu' => 'register_ocorrencia_photos'
                ];
            }
            
            $this->redisSessionService->deleteSession($phone);
            return [
                'message' => "❌ Erro: Nenhum uso ativo encontrado.",
                'menu' => 'none'
            ];
        }

        // NOVO FLUXO: Adicionando fotos à ocorrência
        if ($currentMenu === 'register_ocorrencia_photos') {
            $occurrenceId = $this->redisSessionService->getSessionData($phone, 'occurrence_id');
            $photosCount = $this->redisSessionService->getSessionData($phone, 'photos_count', 0);
            
            if (!$occurrenceId) {
                $this->redisSessionService->deleteSession($phone);
                return [
                    'message' => "❌ Erro: Sessão perdida. Tente novamente.",
                    'menu' => 'main_menu'
                ];
            }
            
            // Se usuário escolheu finalizar
            if ($message === '1') {
                return $this->finalizeOccurrence($phone, $occurrenceId, $photosCount);
            }
            
            // Se usuário escolheu cancelar
            if ($message === '2') {
                return $this->cancelOccurrence($phone, $occurrenceId);
            }
            
            // Se é uma imagem
            if ($messageType === 'image' && $imageData) {
                return $this->addPhotoToOccurrence($phone, $occurrenceId, $imageData, $caption, $photosCount);
            }
            
            // Se é texto mas não é "1" ou "2", explicar novamente
            return [
                'message' => "📷 Por favor:\n\n" .
                           "• Envie uma foto da ocorrência, OU\n" .
                           "• Digite '1' para finalizar, OU\n" .
                           "• Digite '2' para cancelar\n\n" .
                           "📊 Fotos adicionadas: {$photosCount}",
                'menu' => 'register_ocorrencia_photos'
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

    private function addPhotoToOccurrence($phone, $occurrenceId, $imageData, $caption, $currentCount)
    {
        try {
            // Salva a imagem usando o WhatsAppMediaService
            $mediaService = app(WhatsAppMediaService::class);
            $savedImage = $mediaService->saveImageFromBase64($imageData, $caption);
            
            if (!$savedImage) {
                return [
                    'message' => "❌ Erro ao salvar a imagem. Tente novamente.\n\n" .
                               "📊 Fotos já adicionadas: {$currentCount}",
                    'menu' => 'register_ocorrencia_photos'
                ];
            }
            
            // Salva a foto da ocorrência
            OccurrencePhoto::create([
                'occurrence_id' => $occurrenceId,
                'filename' => $savedImage['filename'],
                'original_filename' => $savedImage['original_filename'],
                'path' => $savedImage['path'],
                'mime_type' => $savedImage['mime_type'],
                'size' => $savedImage['size'],
                'caption' => $caption ?: 'Sem legenda'
            ]);
            
            // Atualiza contador de fotos
            $newCount = $currentCount + 1;
            $this->redisSessionService->setSessionData($phone, 'photos_count', $newCount);
            
            $captionText = $caption ? "'{$caption}'" : 'sem legenda';
            
            return [
                'message' => "✅ Foto {$newCount} salva com sucesso!\n\n" .
                           "📝 Legenda: {$captionText}\n" .
                           "📊 Total de fotos: {$newCount}\n\n" .
                           "📷 Continue enviando fotos ou digite '1' para finalizar.",
                'menu' => 'register_ocorrencia_photos'
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar foto à ocorrência:', [
                'error' => $e->getMessage(),
                'occurrence_id' => $occurrenceId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'message' => "❌ Erro interno ao processar a imagem. Tente novamente.",
                'menu' => 'register_ocorrencia_photos'
            ];
        }
    }

    private function finalizeOccurrence($phone, $occurrenceId, $photosCount)
    {
        try {
            $occurrence = Occurrence::with(['vehicleUsage.user', 'vehicleUsage.vehicle', 'photos'])->find($occurrenceId);
            
            if (!$occurrence) {
                $this->redisSessionService->deleteSession($phone);
                return [
                    'message' => "❌ Erro: Ocorrência não encontrada.",
                    'menu' => 'main_menu'
                ];
            }
            
            // Notificar supervisores sobre a ocorrência finalizada
            $photoText = $photosCount > 0 ? "\n📸 {$photosCount} foto(s) anexada(s)" : "\n📸 Nenhuma foto anexada";
            
            $this->notifySupervisors(
                "⚠️ OCORRÊNCIA FINALIZADA\n\n" .
                "👤 Usuário: {$occurrence->vehicleUsage->user->name}\n" .
                "📱 Telefone: {$occurrence->vehicleUsage->user->phone}\n" .
                "🚙 Veículo: {$occurrence->vehicleUsage->vehicle->brand} {$occurrence->vehicleUsage->vehicle->model}\n" .
                "🏷️ Placa: {$occurrence->vehicleUsage->vehicle->plate}\n" .
                "📝 Descrição: {$occurrence->description}" .
                $photoText . "\n" .
                "🕐 Horário: " . $occurrence->created_at->format('d/m/Y H:i')
            );
            
            $this->redisSessionService->deleteSession($phone);
            
            return [
                'message' => "✅ Ocorrência registrada com sucesso!\n\n" .
                           "📝 Descrição: {$occurrence->description}\n" .
                           "📸 Fotos anexadas: {$photosCount}\n" .
                           "🚗 Veículo: {$occurrence->vehicleUsage->vehicle->brand} {$occurrence->vehicleUsage->vehicle->model}\n" .
                           "🏷️ Placa: {$occurrence->vehicleUsage->vehicle->plate}\n" .
                           "🕐 Data/Hora: " . $occurrence->created_at->format('d/m/Y H:i') . "\n\n" .
                           "Obrigado pelo registro! 📋",
                'menu' => 'none'
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao finalizar ocorrência:', [
                'error' => $e->getMessage(),
                'occurrence_id' => $occurrenceId,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->redisSessionService->deleteSession($phone);
            return [
                'message' => "❌ Erro interno ao finalizar ocorrência. Tente novamente.",
                'menu' => 'main_menu'
            ];
        }
    }

    private function cancelOccurrence($phone, $occurrenceId)
    {
        try {
            $occurrence = Occurrence::with(['photos'])->find($occurrenceId);
            
            if (!$occurrence) {
                $this->redisSessionService->deleteSession($phone);
                return [
                    'message' => "❌ Erro: Ocorrência não encontrada.",
                    'menu' => 'main_menu'
                ];
            }
            
            // Deletar todas as fotos físicas do storage
            $mediaService = app(WhatsAppMediaService::class);
            foreach ($occurrence->photos as $photo) {
                $mediaService->deletePhoto($photo->path);
            }
            
            // Deletar a ocorrência (cascade vai deletar as fotos do banco)
            $occurrence->delete();
            
            // Limpar sessão
            $this->redisSessionService->deleteSession($phone);
            
            Log::info('Ocorrência cancelada pelo usuário:', [
                'occurrence_id' => $occurrenceId,
                'phone' => $phone,
                'photos_deleted' => $occurrence->photos->count()
            ]);
            
            return [
                'message' => "🗑️ Ocorrência cancelada com sucesso!\n\n" .
                           "📝 Descrição: {$occurrence->description}\n" .
                           "📸 {$occurrence->photos->count()} foto(s) removida(s)\n" .
                           "🚮 Todos os dados foram apagados\n\n" .
                           "Voltando ao menu principal...",
                'menu' => 'main_menu',
                'send_menu_next' => true
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar ocorrência:', [
                'error' => $e->getMessage(),
                'occurrence_id' => $occurrenceId,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->redisSessionService->deleteSession($phone);
            return [
                'message' => "❌ Erro interno ao cancelar ocorrência. Sessão limpa.",
                'menu' => 'main_menu'
            ];
        }
    }
} 
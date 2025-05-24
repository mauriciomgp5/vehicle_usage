<?php

namespace App\Http\Controllers;

use App\Services\MenuService;
use App\Services\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController extends Controller
{
    protected $menuService;
    protected $evolutionApiService;

    public function __construct(
        MenuService $menuService,
        EvolutionApiService $evolutionApiService
    ) {
        $this->menuService = $menuService;
        $this->evolutionApiService = $evolutionApiService;
    }

    public function handleWebhook(Request $request)
    {
        try {
            // Log dos dados recebidos
            Log::info('Evolution API Webhook recebido:', [
                'payload' => $request->all()
            ]);

            // Aqui você pode processar os diferentes tipos de eventos
            $eventType = $request->input('event');
            
            switch ($eventType) {
                case 'messages.upsert':
                    $this->handleMessagesUpsert($request);
                    break;
                case 'messages.update':
                    $this->handleMessagesUpdate($request);
                    break;
                case 'messages.delete':
                    $this->handleMessagesDelete($request);
                    break;
                case 'connection.update':
                    $this->handleConnectionUpdate($request);
                    break;
                default:
                    Log::warning('Tipo de evento não tratado:', ['event' => $eventType]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function handleMessagesUpsert(Request $request)
    {
        $messages = $request->input('data');
        
        foreach ($messages as $message) {
            if (isset($message['key']['fromMe']) && !$message['key']['fromMe']) {
                $phone = $message['key']['remoteJid'];
                $messageContent = $message['message']['conversation'] ?? 
                                $message['message']['extendedTextMessage']['text'] ?? 
                                '';

                if (!empty($messageContent)) {
                    $response = $this->menuService->handleUserResponse($phone, $messageContent);
                    
                    // Envia a resposta via Evolution API
                    $this->evolutionApiService->sendMessage($phone, $response['message']);
                }
            }
        }
    }

    private function handleMessagesUpdate(Request $request)
    {
        // Implementar lógica para atualização de mensagens
        Log::info('Processando atualização de mensagem');
    }

    private function handleMessagesDelete(Request $request)
    {
        // Implementar lógica para exclusão de mensagens
        Log::info('Processando exclusão de mensagem');
    }

    private function handleConnectionUpdate(Request $request)
    {
        // Implementar lógica para atualizações de conexão
        Log::info('Processando atualização de conexão');
    }
} 
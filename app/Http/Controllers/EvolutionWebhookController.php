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
            // // Log::info('Evolution API Webhook recebido:', [
            // //     'payload' => $request->all()
            // // ]);

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
                    // Log::warning('Tipo de evento não tratado:', ['event' => $eventType]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            // Log::error('Erro ao processar webhook:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function handleMessagesUpsert(Request $request)
    {
        $data = $request->input('data');
        
        // Log::info('Processando mensagens:', [
        //     'data' => $data
        // ]);
        
        // Verifica se é uma mensagem única ou um array de mensagens
        $messages = is_array($data) && isset($data[0]) ? $data : [$data];
        
        foreach ($messages as $message) {
            // Log::info('Processando mensagem individual:', [
            //     'message' => $message
            // ]);

            if (isset($message['key']['fromMe']) && !$message['key']['fromMe']) {
                $phone = $message['key']['remoteJid'];
                
                // Extrair conteúdo da mensagem (texto ou imagem)
                $messageContent = '';
                $messageType = 'text';
                $mediaId = null;
                $caption = null;
                $imageData = null;
                
                // Mensagem de texto
                if (isset($message['message']['conversation'])) {
                    $messageContent = $message['message']['conversation'];
                } elseif (isset($message['message']['extendedTextMessage']['text'])) {
                    $messageContent = $message['message']['extendedTextMessage']['text'];
                }
                // Mensagem de imagem
                elseif (isset($message['message']['imageMessage'])) {
                    $messageType = 'image';
                    $mediaId = $message['key']['id'];
                    $caption = $message['message']['imageMessage']['caption'] ?? '';
                    $messageContent = $caption; // Usa a legenda como conteúdo
                    
                    // LOG DETALHADO DA IMAGEM - ESTRUTURA COMPLETA
                    // Log::info('🖼️ IMAGEM DETECTADA - ESTRUTURA COMPLETA:', [
                    //     'phone' => $phone,
                    //     'mediaId' => $mediaId,
                    //     'caption' => $caption,
                    //     'imageMessage_completa' => $message['message']['imageMessage'],
                    //     'message_keys' => array_keys($message['message']),
                    //     'has_base64_in_message' => isset($message['message']['base64']),
                    //     'has_base64_in_imageMessage' => isset($message['message']['imageMessage']['base64'])
                    // ]);
                    
                    // CORREÇÃO: base64 está em message.base64, não em imageMessage.base64
                    $imageData = [
                        'base64' => $message['message']['base64'] ?? null, // ✅ CORRIGIDO!
                        'mimetype' => $message['message']['imageMessage']['mimetype'] ?? 'image/jpeg',
                        'filename' => $message['message']['imageMessage']['filename'] ?? 'image.jpg'
                    ];
                    
                    // LOG ESPECÍFICO DOS DADOS EXTRAÍDOS
                    // Log::info('📋 DADOS EXTRAÍDOS DA IMAGEM:', [
                    //     'phone' => $phone,
                    //     'mediaId' => $mediaId,
                    //     'caption' => $caption,
                    //     'hasBase64' => !empty($imageData['base64']),
                    //     'base64Length' => !empty($imageData['base64']) ? strlen($imageData['base64']) : 0,
                    //     'base64Preview' => !empty($imageData['base64']) ? substr($imageData['base64'], 0, 50) . '...' : 'VAZIO',
                    //     'mimetype' => $imageData['mimetype'],
                    //     'filename' => $imageData['filename']
                    // ]);
                }

                // Log::info('Dados extraídos da mensagem:', [
                //     'phone' => $phone,
                //     'messageContent' => $messageContent,
                //     'messageType' => $messageType,
                //     'mediaId' => $mediaId
                // ]);

                if ($messageContent !== '' || $messageType === 'image') {
                    try {
                        $response = $this->menuService->handleUserResponse(
                            $phone, 
                            $messageContent, 
                            $messageType, 
                            $mediaId, 
                            $caption,
                            $imageData
                        );
                        
                        // Log::info('Resposta do MenuService:', [
                        //     'response' => $response
                        // ]);
                        
                        // Envia a primeira mensagem
                        $result = $this->evolutionApiService->sendMessage($phone, $response['message']);
                        
                        // Log::info('Resultado do envio da primeira mensagem:', [
                        //     'success' => $result
                        // ]);
                        
                        // Se tiver send_menu_next, envia o menu em uma segunda mensagem
                        if (isset($response['send_menu_next']) && $response['send_menu_next']) {
                            sleep(1); // Pequeno delay para garantir ordem das mensagens
                            $menuMessage = $this->menuService->getMenuMessage($response['menu']);
                            $menuResult = $this->evolutionApiService->sendMessage($phone, $menuMessage);
                            
                            // Log::info('Resultado do envio do menu:', [
                            //     'success' => $menuResult
                            // ]);
                        }
                    } catch (\Exception $e) {
                        // Log::error('Erro ao processar resposta:', [
                        //     'error' => $e->getMessage(),
                        //     'trace' => $e->getTraceAsString()
                        // ]);
                    }
                }
            }
        }
    }

    private function handleMessagesUpdate(Request $request)
    {
        // Implementar lógica para atualização de mensagens
        // Log::info('Processando atualização de mensagem');
    }

    private function handleMessagesDelete(Request $request)
    {
        // Implementar lógica para exclusão de mensagens
        // Log::info('Processando exclusão de mensagem');
    }

    private function handleConnectionUpdate(Request $request)
    {
        // Implementar lógica para atualizações de conexão
        // Log::info('Processando atualização de conexão');
    }
} 
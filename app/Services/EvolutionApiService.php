<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.evolution_api.url');
        $this->apiKey = config('services.evolution_api.key');
    }

    public function sendMessage(
        string $phone,
        string $message,
        ?int $delay = null,
        bool $linkPreview = false,
        bool $mentionsEveryOne = false,
        array $mentioned = [],
        ?array $quoted = null
    ) {
        try {
            $payload = [
                'number' => $phone,
                'text' => $message
            ];

            if ($delay !== null) {
                $payload['delay'] = $delay;
            }

            if ($linkPreview) {
                $payload['linkPreview'] = true;
            }

            if ($mentionsEveryOne) {
                $payload['mentionsEveryOne'] = true;
            }

            if (!empty($mentioned)) {
                $payload['mentioned'] = $mentioned;
            }

            if ($quoted !== null) {
                $payload['quoted'] = $quoted;
            }

            $instance = config('services.evolution_api.instance', '999999999');
            $url = "{$this->baseUrl}/message/sendText/{$instance}";

            Log::info('Enviando mensagem para Evolution API:', [
                'url' => $url,
                'payload' => $payload
            ]);

            $response = Http::withHeaders([
                'apikey' => $this->apiKey
            ])->post($url, $payload);

            if ($response->successful()) {
                Log::info('Mensagem enviada com sucesso:', [
                    'phone' => $phone,
                    'response' => $response->json()
                ]);
                return true;
            }

            Log::error('Erro ao enviar mensagem:', [
                'phone' => $phone,
                'error' => $response->json(),
                'status' => $response->status()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exceção ao enviar mensagem:', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function sendQuotedMessage(
        string $phone,
        string $message,
        string $quotedMessageId,
        string $quotedText
    ) {
        return $this->sendMessage(
            $phone,
            $message,
            null,
            false,
            false,
            [],
            [
                'key' => [
                    'id' => $quotedMessageId
                ],
                'message' => [
                    'conversation' => $quotedText
                ]
            ]
        );
    }
} 
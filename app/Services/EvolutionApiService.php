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

    public function sendMessage(string $phone, string $message)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey
            ])->post("{$this->baseUrl}/message/sendText", [
                'number' => $phone,
                'text' => $message
            ]);

            if ($response->successful()) {
                Log::info('Mensagem enviada com sucesso:', [
                    'phone' => $phone,
                    'response' => $response->json()
                ]);
                return true;
            }

            Log::error('Erro ao enviar mensagem:', [
                'phone' => $phone,
                'error' => $response->json()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exceção ao enviar mensagem:', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
} 
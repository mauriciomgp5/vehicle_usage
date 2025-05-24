<?php

namespace App\Console\Commands;

use App\Services\RedisSessionService;
use Illuminate\Console\Command;

class TestRedisSession extends Command
{
    protected $signature = 'test:redis-session {phone}';
    protected $description = 'Testa o RedisSessionService com um número de telefone';

    protected $redisSessionService;

    public function __construct(RedisSessionService $redisSessionService)
    {
        parent::__construct();
        $this->redisSessionService = $redisSessionService;
    }

    public function handle()
    {
        $phone = $this->argument('phone');
        
        $this->info("Testando Redis Session para o número: {$phone}");
        
        // Teste 1: Criar sessão
        $this->info("\n1. Criando sessão inicial...");
        $this->redisSessionService->updateMenu($phone, 'main_menu');
        $session = $this->redisSessionService->getSession($phone);
        $this->info("Sessão criada: " . json_encode($session, JSON_PRETTY_PRINT));
        
        // Teste 2: Atualizar menu
        $this->info("\n2. Atualizando menu para vehicle_usage...");
        $this->redisSessionService->updateMenu($phone, 'vehicle_usage');
        $session = $this->redisSessionService->getSession($phone);
        $this->info("Menu atualizado: " . json_encode($session, JSON_PRETTY_PRINT));
        
        // Teste 3: Adicionar dados na sessão
        $this->info("\n3. Adicionando dados na sessão...");
        $this->redisSessionService->setSessionData($phone, 'test_key', 'test_value');
        $session = $this->redisSessionService->getSession($phone);
        $this->info("Dados adicionados: " . json_encode($session, JSON_PRETTY_PRINT));
        
        // Teste 4: Recuperar dados específicos
        $this->info("\n4. Recuperando dados específicos...");
        $testValue = $this->redisSessionService->getSessionData($phone, 'test_key');
        $this->info("Valor recuperado: {$testValue}");
        
        // Teste 5: Verificar TTL
        $this->info("\n5. Verificando TTL da sessão...");
        $ttl = $this->redisSessionService->getTTL($phone);
        $this->info("TTL restante: {$ttl} segundos");
        
        $this->info("\nTestes concluídos com sucesso!");
    }
} 
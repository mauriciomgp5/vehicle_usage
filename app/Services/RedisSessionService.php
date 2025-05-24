<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;

class RedisSessionService
{
    protected $redis;
    protected $prefix = 'whatsapp:session:';
    protected $ttl = 3600; // 1 hora em segundos

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    public function getSession(string $phone): array
    {
        $session = $this->redis->get($this->prefix . $phone);
        
        if (!$session) {
            return [
                'current_menu' => 'main_menu',
                'last_interaction' => now()->timestamp,
                'session_data' => []
            ];
        }

        return json_decode($session, true);
    }

    public function updateSession(string $phone, array $data): void
    {
        $session = $this->getSession($phone);
        $session = array_merge($session, $data);
        $session['last_interaction'] = now()->timestamp;

        $this->redis->setex(
            $this->prefix . $phone,
            $this->ttl,
            json_encode($session)
        );
    }

    public function deleteSession(string $phone): void
    {
        $this->redis->del($this->prefix . $phone);
    }

    public function updateMenu(string $phone, string $menu): void
    {
        $this->updateSession($phone, ['current_menu' => $menu]);
    }

    public function getCurrentMenu(string $phone): string
    {
        $session = $this->getSession($phone);
        return $session['current_menu'];
    }

    public function setSessionData(string $phone, string $key, $value): void
    {
        $session = $this->getSession($phone);
        $session['session_data'][$key] = $value;
        $this->updateSession($phone, $session);
    }

    public function getSessionData(string $phone, string $key, $default = null)
    {
        $session = $this->getSession($phone);
        return $session['session_data'][$key] ?? $default;
    }

    public function getTTL(string $phone): int
    {
        return $this->redis->ttl($this->prefix . $phone);
    }
} 
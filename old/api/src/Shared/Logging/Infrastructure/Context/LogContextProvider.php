<?php

namespace App\Shared\Logging\Infrastructure\Context;

use Symfony\Component\HttpFoundation\RequestStack;

class LogContextProvider
{
    public function __construct(
        private RequestStack $requestStack,
        private RequestIdProvider $requestIdProvider
    ) {}

    /**
     * Fournit le contexte système (HTTP / CLI)
     */
    public function getSystemContext(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        // =========================
        // 🖥️ CLI / CRON / WORKER
        // =========================
        if (!$request) {
            return [
                'request_id' => $this->requestIdProvider->get(),
                'domain' => 'cli',
                'uri' => null,
                'method' => null,
                'ip' => '127.0.0.1',
            ];
        }

        // =========================
        // 🌐 HTTP
        // =========================
        return [
            'request_id' => $this->requestIdProvider->get(),
            'domain' => $this->resolveHost($request),
            'uri' => $request->getRequestUri(),
            'method' => $request->getMethod(),
            'ip' => $this->resolveClientIp($request),
        ];
    }

    /**
     * Résout le host de manière sécurisée
     */
    private function resolveHost($request): string
    {
        try {
            return $request->getHost() ?: 'unknown';
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    /**
     * Résout l'IP client (compatible proxy)
     */
    private function resolveClientIp($request): string
    {
        $ip = $request->getClientIp();

        return $ip ?: '0.0.0.0';
    }
}
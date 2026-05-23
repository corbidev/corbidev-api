<?php

namespace App\Shared\Logging\Infrastructure\Context;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestIdProvider
{
    private ?string $requestId = null;

    public function __construct(
        private readonly RequestStack $requestStack
    ) {}

    /**
     * 🎯 Retourne un request_id unique et stable
     */
    public function get(): string
    {
        if ($this->requestId !== null) {
            return $this->requestId;
        }

        $request = $this->requestStack->getCurrentRequest();

        // =========================
        // 🌐 HTTP → header client
        // =========================
        if ($request !== null) {
            $header = $request->headers->get('X-Request-Id');

            if (!empty($header)) {
                return $this->requestId = $this->sanitize($header);
            }
        }

        // =========================
        // 🖥️ CLI / CRON / FALLBACK
        // =========================
        return $this->requestId = $this->generate();
    }

    /**
     * 🔒 Génération sécurisée
     */
    private function generate(): string
    {
        return 'req_' . bin2hex(random_bytes(16));
    }

    /**
     * 🧼 Nettoyage (sécurité)
     */
    private function sanitize(string $value): string
    {
        // On limite la taille + caractères safe
        $value = substr($value, 0, 64);

        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $value) ?: $this->generate();
    }
}
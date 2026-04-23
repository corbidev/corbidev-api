<?php

namespace App\Shared\Logging\Infrastructure\Monolog;

use Monolog\LogRecord;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class LogContextProcessor
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $record->context;

        // 🔐 USER
        $user = $this->security->getUser();
        if ($user && method_exists($user, 'getId') && !isset($context['userId'])) {
            $context['userId'] = $user->getId();
        }

        // 🌐 CLIENT dynamique (multi-API / multisite)
        if (!isset($context['client'])) {
            $context['client'] = $this->resolveClient($request);
        }

        // 🔗 REQUEST ID
        if ($request && $request->headers->has('X-Request-Id')) {
            $context['request_id'] = $request->headers->get('X-Request-Id');
        } elseif (!isset($context['request_id'])) {
            // fallback si absent
            $context['request_id'] = uniqid('req_', true);
        }

        // 📊 HTTP STATUS (injecté via subscriber ou exception listener)
        if ($request && $request->attributes->has('http_status')) {
            $context['httpStatus'] = $request->attributes->get('http_status');
        }

        return $record->with(context: $context);
    }

    /**
     * Détermine dynamiquement l'origine de l'API
     */
    private function resolveClient($request): string
    {
        if (!$request) {
            return 'cli';
        }

        $path = $request->getPathInfo();
        $host = $request->getHost();

        // 🔥 PRIORITÉ 1 : attribut explicite (le plus propre)
        if ($request->attributes->has('client')) {
            return $request->attributes->get('client');
        }

        // 🔥 PRIORITÉ 2 : basé sur le path
        if (str_starts_with($path, '/auth')) {
            return 'auth-api';
        }

        if (str_starts_with($path, '/admin')) {
            return 'admin-api';
        }

        if (str_starts_with($path, '/api')) {
            return 'api';
        }

        // 🔥 PRIORITÉ 3 : basé sur le host (multi-domaine)
        if (str_contains($host, 'auth')) {
            return 'auth-api';
        }

        if (str_contains($host, 'admin')) {
            return 'admin-api';
        }

        // 🔥 fallback
        return 'main-api';
    }
}
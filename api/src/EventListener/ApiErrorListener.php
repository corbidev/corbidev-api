<?php

namespace App\EventListener;

use App\Http\HttpStatusProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiErrorListener
{
    public function __construct(
        private readonly HttpStatusProvider $statusProvider
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        $status = $this->resolveStatusCode($exception);
        $statusData = $this->statusProvider->get($status);

        $responseData = [
            'type' => 'about:blank',
            'title' => $statusData['message'],
            'status' => $status,
            'detail' => $this->getSafeDetail($status, $exception),
            'description' => $statusData['description'],
            'instance' => $request->getPathInfo(),
            'trace_id' => $this->generateTraceId(),
        ];

        $response = new JsonResponse($responseData, $status);
        $response->headers->set('Content-Type', 'application/problem+json');

        $event->setResponse($response);
    }

    /**
     * Détermine le code HTTP à partir de l'exception.
     */
    private function resolveStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return 500;
    }

    /**
     * Retourne un message sécurisé (évite fuite d'infos sensibles).
     */
    private function getSafeDetail(int $status, \Throwable $exception): string
    {
        // 👉 En prod, ne jamais exposer les erreurs internes
        if ($status >= 500) {
            return 'Une erreur interne est survenue.';
        }

        return $exception->getMessage() ?: 'Une erreur est survenue.';
    }

    /**
     * Génère un identifiant unique pour tracer l’erreur (logs).
     */
    private function generateTraceId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
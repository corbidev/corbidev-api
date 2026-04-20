<?php

namespace App\EventListener;

use App\Http\HttpStatusProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class ApiErrorListener
{
    public function __construct(
        private readonly HttpStatusProvider $statusProvider,
        private readonly LoggerInterface $logger
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        $status = $this->resolveStatusCode($exception);
        $statusData = $this->statusProvider->get($status);

        // 🔥 LOG ICI (IMPORTANT)
        if ($status >= 500) {
            $this->logger->error('API Exception', [
                'status' => $status,
                'message' => $exception->getMessage(),
                'route' => $request->getPathInfo(),
                'method' => $request->getMethod(),
                'ip' => $request->getClientIp(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

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

    private function resolveStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return 500;
    }

    private function getSafeDetail(int $status, \Throwable $exception): string
    {
        if ($status >= 500) {
            return 'Une erreur interne est survenue.';
        }

        return $exception->getMessage() ?: 'Une erreur est survenue.';
    }

    private function generateTraceId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
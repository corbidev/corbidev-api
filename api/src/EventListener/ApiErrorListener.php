<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiErrorListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        $status = 500;

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
        }

        $data = [
            'type' => 'about:blank', // ✅ standard RFC
            'title' => $this->getDefaultTitle($status),
            'status' => $status,
            'detail' => $exception->getMessage(),
            'instance' => $request->getPathInfo(),
        ];

        $response = new JsonResponse($data, $status);
        $response->headers->set('Content-Type', 'application/problem+json');

        $event->setResponse($response);
    }

    private function getDefaultTitle(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }
}
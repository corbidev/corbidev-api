<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Exception;

use App\Shared\Infrastructure\Http\JsonResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

final class ApiExceptionListener
{
    public function __construct(
        private readonly ExceptionMapper $mapper,
        private readonly JsonResponseFactory $responseFactory,
        private readonly LoggerInterface $logger
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // 🔥 Mapper = source unique (error + status)
        [$apiError, $status] = $this->mapper->mapWithStatus($exception);

        $request = $event->getRequest();

        // 🔥 IMPORTANT : injecter le httpStatus pour les logs
        $request->attributes->set('http_status', $status);

        // 🔥 Contexte structuré (propre et exploitable)
        $context = [
            'httpStatus' => $status,
            'errorCode' => $apiError->getCode()->value,
            'businessCode' => $apiError->getBusinessCode()?->value,
            'exceptionClass' => $exception::class,
            'message' => $exception->getMessage(),
        ];

        // 🔥 Stack trace uniquement pour erreurs serveur
        if ($status >= 500) {
            $context['trace'] = $exception->getTraceAsString();
        }

        // 🔥 Logging (sera enrichi automatiquement par ton processor)
        if ($status >= 500) {
            $this->logger->error('API Exception', $context);
        } else {
            $this->logger->warning('API Client Error', $context);
        }

        // 🔥 Réponse JSON standardisée
        $response = $this->responseFactory->error($apiError, $status);

        $event->setResponse($response);
    }
}
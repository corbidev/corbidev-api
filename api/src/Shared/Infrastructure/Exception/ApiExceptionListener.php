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

        // 🔥 Logging structuré
        $context = [
            'status' => $status,
            'error_code' => $apiError->getCode()->value,
            'business_code' => $apiError->getBusinessCode()?->value,
            'message' => $exception->getMessage(),
            'exception_class' => $exception::class,
            'trace' => $status >= 500 ? $exception->getTraceAsString() : null,
        ];

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
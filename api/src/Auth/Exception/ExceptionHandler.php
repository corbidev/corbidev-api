<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use App\Auth\Dto\ValidateResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Transforme les exceptions de l'API Auth en réponses HTTP homogènes.
 */
final readonly class ExceptionHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Traite les exceptions de l'API.
     */
    #[AsEventListener('kernel.exception')]
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof Exception) {
            $request = $event->getRequest();
            $locale = $request->getPreferredLanguage(ErrorCode::availableLocales()) ?? 'fr-FR';
            $error = ErrorCode::fromCode($exception->getErrorCode(), $locale);
            $statusCode = $exception->getStatusCode() ?? $error->getStatusCode();
            $messageArgs = $exception->getMessageArgs();

            if ($statusCode >= 500) {
                $this->logger->error(
                    $error->formatLogMessage($messageArgs) ?? $exception->getMessage(),
                    [
                        'errorCode' => $error->getCode(),
                        'statusCode' => $statusCode,
                        'locale' => $locale,
                        'messageArgs' => $messageArgs,
                        'exception' => $exception,
                    ],
                );
            }

            $event->setResponse(
                new JsonResponse(
                    new ValidateResponse(
                        valid: false,
                        message: $error->formatPublicMessage($messageArgs),
                        errorCode: $error->getCode(),
                    ),
                    $statusCode,
                ),
            );

            return;
        }
    }
}
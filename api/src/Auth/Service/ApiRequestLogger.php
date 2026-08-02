<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Dto\ValidateRequest;
use Psr\Log\LoggerInterface;

/**
 * Journalise les validations de requêtes API.
 */
final readonly class ApiRequestLogger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Journalise une tentative de validation.
     */
    public function log(ValidateRequest $request): void
    {
        $this->logger->info(
            'Validation API',
            [
                'clientId' => $request->clientId,
                'method' => $request->method,
                'uri' => $request->uri,
                'timestamp' => $request->timestamp,
                'nonce' => $request->nonce,
            ],
        );
    }
}
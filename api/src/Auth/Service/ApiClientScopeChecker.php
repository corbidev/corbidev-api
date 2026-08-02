<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Entity\ApiClient;
use App\Auth\Exception\Exception;
use App\Auth\Repository\ApiClientScopeRepository;

/**
 * Vérifie qu'un client API possède le scope requis.
 */
final readonly class ApiClientScopeChecker
{
    public function __construct(
        private ApiClientScopeRepository $scopeRepository,
    ) {}

    /**
     * Vérifie que le client possède le scope demandé.
     *
     * @param ApiClient $client Client API.
     * @param string $requiredScope Scope requis.
     *
     * @throws Exception Si le client ne possède pas le scope demandé.
     */
    public function check(
        ApiClient $client,
        string $requiredScope,
    ): void {
        if (!$this->scopeRepository->exists($client, $requiredScope)) {
            throw Exception::client(
                'AUTH_010',
                null,
                [$requiredScope, $client->getClientId(), $requiredScope],
            );
        }
    }
}
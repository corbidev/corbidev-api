<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Entity\ApiClient;
use App\Auth\Exception\Exception;

/**
 * Vérifie qu'un client API est autorisé à s'authentifier.
 */
final readonly class ApiClientAuthenticator
{
    /**
     * Vérifie que le client API est actif.
     *
     * @throws Exception Si le client est désactivé.
     */
    public function authenticate(ApiClient $client): void
    {
        if (!$client->isEnabled()) {
            throw Exception::client('AUTH_007');
        }
    }
}
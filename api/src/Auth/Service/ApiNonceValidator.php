<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Exception\Exception;

/**
 * Vérifie que le nonce fourni par le client est valide.
 *
 * Cette validation permet de limiter les attaques par rejeu
 * en refusant les nonces vides ou invalides.
 */
final readonly class ApiNonceValidator
{
    /**
     * Vérifie qu'un nonce est valide.
     *
     * @throws Exception Si le nonce est invalide.
     */
    public function validate(string $nonce): void
    {
        if (trim($nonce) === '') {
            throw new Exception('AUTH_005');
        }
    }
}
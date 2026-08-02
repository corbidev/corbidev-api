<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Exception\Exception;

/**
 * Vérifie que la signature HMAC fournie par le client est valide.
 */
final readonly class ApiSignatureValidator
{
    /**
     * Vérifie que la signature attendue correspond à la signature reçue.
     *
     * @param string $expectedSignature Signature calculée.
     * @param string $receivedSignature Signature reçue du client.
     *
     * @throws Exception Si la signature est invalide.
     */
    public function validate(
        string $expectedSignature,
        string $receivedSignature,
    ): void {
        if (!hash_equals($expectedSignature, $receivedSignature)) {
            throw Exception::client('AUTH_002');
        }
    }
}
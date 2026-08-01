<?php

declare(strict_types=1);

namespace App\Auth\Service;

/**
 * Assure le hachage et la vérification des secrets des clients API.
 *
 * Les secrets sont stockés sous forme de hash afin qu'ils ne puissent
 * jamais être retrouvés en cas de compromission de la base de données.
 */
final readonly class ApiSecretHasher
{
    /**
     * Génère un hash sécurisé d'un secret.
     *
     * @param string $secret Secret en clair.
     *
     * @return string Hash du secret.
     */
    public function hash(string $secret): string
    {
        return password_hash($secret, PASSWORD_DEFAULT);
    }

    /**
     * Vérifie qu'un secret correspond au hash enregistré.
     *
     * @param string $plainSecret  Secret fourni par le client.
     * @param string $hashedSecret Hash enregistré en base de données.
     *
     * @return bool True si le secret est valide, sinon false.
     */
    public function verify(
        string $plainSecret,
        string $hashedSecret,
    ): bool {
        return password_verify($plainSecret, $hashedSecret);
    }
}
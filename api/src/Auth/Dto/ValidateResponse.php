<?php

declare(strict_types=1);

namespace App\Auth\Dto;

/**
 * Représente le résultat de la validation d'une requête HMAC.
 *
 * Ce DTO est retourné par l'API Auth afin d'indiquer à l'API appelante
 * si la requête peut être traitée.
 */
final readonly class ValidateResponse
{
    /**
     * @param bool        $valid      Indique si la requête est valide et autorisée.
     * @param string|null $message    Message décrivant le résultat de la validation.
     * @param string|null $errorCode  Code d'erreur fonctionnel renvoyé par l'API.
     * @param string|null $clientId   Identifiant public du client authentifié.
     */
    public function __construct(
        public bool $valid,
        public ?string $message = null,
        public ?string $errorCode = null,
        public ?string $clientId = null,
    ) {}
}
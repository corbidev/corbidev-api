<?php

declare(strict_types=1);

namespace App\Auth\Dto;

/**
 * Représente une demande de validation HMAC transmise à l'API Auth.
 *
 * Ce DTO contient uniquement les informations nécessaires à Auth pour :
 * - identifier le client ;
 * - vérifier la signature HMAC ;
 * - contrôler les autorisations du client ;
 * - journaliser la requête.
 */
final readonly class ValidateRequest
{
    /**
     * @param string      $clientId  Identifiant public du client.
     * @param int         $timestamp Horodatage Unix utilisé pour prévenir les attaques par rejeu.
     * @param string      $nonce     Valeur unique garantissant l'unicité de la requête.
     * @param string      $signature Signature HMAC calculée par le client.
     * @param string      $method    Méthode HTTP de la requête.
     * @param string      $uri       URI de la ressource demandée.
     * @param string|null $body      Corps brut de la requête, si présent.
     */
    public function __construct(
        public string $clientId,
        public int $timestamp,
        public string $nonce,
        public string $signature,
        public string $method,
        public string $uri,
        public ?string $body = null,
    ) {}
}
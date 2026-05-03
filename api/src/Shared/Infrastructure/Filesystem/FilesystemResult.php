<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filesystem;

/**
 * Représente le résultat d'une opération filesystem.
 *
 * Objectif :
 * Fournir un retour explicite, déterministe et sans exception
 * pour toutes les opérations filesystem.
 *
 * Pourquoi :
 * - éviter les exceptions techniques dans les couches supérieures
 * - garantir un état cohérent (success OU failure)
 * - faciliter les tests et le debugging
 *
 * Contraintes :
 * - immuable
 * - état strict (aucune incohérence possible)
 */
final class FilesystemResult
{
    private function __construct(
        private readonly bool $success,
        private readonly ?string $error,
        private readonly ?string $payload,
    ) {
    }

    /**
     * Succès sans données.
     */
    public static function success(): self
    {
        return new self(true, null, null);
    }

    /**
     * Succès avec données (optionnel).
     *
     * @param string $payload
     */
    public static function successWith(string $payload): self
    {
        return new self(true, null, $payload);
    }

    /**
     * Échec avec message d’erreur.
     */
    public static function failure(string $error): self
    {
        return new self(false, $error, null);
    }

    /**
     * Indique si l'opération a réussi.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Indique si l'opération a échoué.
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Retourne l'erreur (si échec).
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Retourne le payload (si succès).
     */
    public function getPayload(): ?string
    {
        return $this->payload;
    }
}
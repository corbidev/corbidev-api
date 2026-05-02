<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Filesystem;

/**
 * Représente le résultat d'une opération filesystem.
 *
 * Pourquoi :
 * Remplacer les exceptions par un modèle explicite, testable
 * et prévisible dans tous les cas.
 */
final class FilesystemResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $content = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(?string $content = null): self
    {
        return new self(true, $content);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }
}
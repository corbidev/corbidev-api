<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging\Emergency;

/**
 * Implémentation native basée sur error_log().
 *
 * Pourquoi :
 * Fournir une écriture réelle sans dépendance externe.
 */
final class NativePhpErrorLogger implements PhpErrorLoggerInterface
{
    public function log(string $message): void
    {
        error_log($message);
    }
}
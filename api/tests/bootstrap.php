<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * 🔥 HANDLER STRICT
 *
 * Objectif :
 * Transformer TOUTES les erreurs PHP (notice, warning, etc.)
 * en exceptions pour forcer l’échec des tests.
 *
 * Pourquoi :
 * PHPUnit 13 n’échoue pas sur les notices par défaut.
 * Cela viole ta règle :
 * 👉 "SI CE N’EST PAS TESTÉ → ÇA N’EXISTE PAS"
 */
set_error_handler(function (
    int $severity,
    string $message,
    string $file,
    int $line
): bool {
    // Respect du error_reporting courant
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new \ErrorException($message, 0, $severity, $file, $line);
});

/**
 * 🔥 ENV SYMFONY
 */
if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

/**
 * 🔥 DEBUG MODE
 */
if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}

set_error_handler(function (
    int $severity,
    string $message,
    string $file,
    int $line
): bool {
    echo "\n🔥 NOTICE DETECTED:\n$message\n$file:$line\n\n";

    throw new \ErrorException($message, 0, $severity, $file, $line);
});

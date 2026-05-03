<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging\Emergency;

/**
 * Abstraction minimale pour écrire dans error_log.
 *
 * Pourquoi :
 * Permet de tester EmergencyLogger sans dépendre
 * de la fonction globale PHP error_log().
 */
interface PhpErrorLoggerInterface
{
    /**
     * Écrit un message dans le système PHP natif.
     *
     * @param string $message Message à écrire
     */
    public function log(string $message): void;
}
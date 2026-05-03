<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging\Emergency;

/**
 * Logger de secours ultime.
 *
 * Pourquoi :
 * Garantir qu'aucune erreur critique ne soit perdue,
 * même si tout le système de logging est défaillant.
 *
 * Contraintes :
 * - Ne jamais lancer d'exception
 * - Ne dépendre d'aucun système externe
 * - Être utilisable dans des contextes instables
 */
final class EmergencyLogger
{
    public function __construct(
        private readonly PhpErrorLoggerInterface $phpLogger
    ) {
    }

    /**
     * Log un message critique sans jamais échouer.
     *
     * @param string $message Message à enregistrer
     */
    public function log(string $message): void
    {
        try {
            $this->phpLogger->log('[EMERGENCY] ' . $message);
        } catch (\Throwable) {
            // ⚠️ SILENCE ABSOLU
            // On ne doit JAMAIS aggraver une panne
        }
    }
}
<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Exception\Exception;
use Symfony\Component\Clock\ClockInterface;

/**
 * Vérifie que le timestamp fourni par le client est compris
 * dans la fenêtre temporelle autorisée.
 *
 * Cette validation permet de limiter les attaques par rejeu
 * (Replay Attack).
 */
final readonly class ApiTimestampValidator
{
    /**
     * @param ClockInterface $clock Horloge utilisée pour obtenir l'heure courante.
     * @param int $allowedDrift Nombre maximal de secondes d'écart autorisé.
     */
    public function __construct(
        private ClockInterface $clock,
        private int $allowedDrift = 300,
    ) {}

    /**
     * Vérifie que le timestamp est valide.
     *
     * @throws Exception Si le timestamp est en dehors de la fenêtre autorisée.
     */
    public function validate(int $timestamp): void
    {
        $now = $this->clock->now()->getTimestamp();

        if (abs($now - $timestamp) > $this->allowedDrift) {
            throw new Exception('AUTH_003');
        }
    }
}
<?php

namespace App\RessLogs\Security;

use App\RessLogs\Entity\LogSource;
use App\RessLogs\Repository\LogSourceRepository;
use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class LogConsumerJwtIssuer implements LogConsumerJwtIssuerInterface
{
    private const DEFAULT_TTL_SECONDS = 3600;

    public function __construct(
        private readonly LogSourceRepository $logSourceRepository,
        private readonly JWTEncoderInterface $jwtEncoder,
    ) {
    }

    public function issueFromCredentials(string $sourceApiKey, string $clientSecret): array
    {
        $normalizedApiKey = trim($sourceApiKey);

        $source = $this->logSourceRepository->findOneBy(['apiKey' => $normalizedApiKey, 'isActive' => true]);

        // Si la source est introuvable, on fait quand même un hash dummy pour éviter
        // les attaques par timing (timing-safe)
        if (!$source instanceof LogSource) {
            password_verify($clientSecret, '$argon2id$v=19$m=65536,t=4,p=1$dummysaltdummysalt$dummyhashvaluedummyhashvaluedummyhashvalue');
            throw new InvalidArgumentException('Identifiants invalides.');
        }

        $storedHash = $source->getClientSecret();
        if ($storedHash === null) {
            throw new InvalidArgumentException('Aucun secret configuré pour cette source. Contactez l\'administrateur.');
        }

        if (!password_verify($clientSecret, $storedHash)) {
            throw new InvalidArgumentException('Identifiants invalides.');
        }

        return $this->issueForSource($source);
    }

    /**
     * @return array{accessToken: string, expiresIn: int}
     */
    private function issueForSource(LogSource $source): array
    {
        $now = time();
        $expiresIn = self::DEFAULT_TTL_SECONDS;

        $token = $this->jwtEncoder->encode([
            'sourceApiKey' => $source->getApiKey(),
            'sourceId' => $source->getId(),
            'sourceName' => $source->getName(),
            'sourceType' => $source->getType(),
            'iat' => $now,
            'exp' => $now + $expiresIn,
        ]);

        return [
            'accessToken' => $token,
            'expiresIn' => $expiresIn,
        ];
    }
}

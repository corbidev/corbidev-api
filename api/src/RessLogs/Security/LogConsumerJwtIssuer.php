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

    public function issueFromSourceApiKey(string $sourceApiKey): array
    {
        $normalizedApiKey = trim($sourceApiKey);
        if ($normalizedApiKey === '') {
            throw new InvalidArgumentException('Le champ «sourceApiKey» est obligatoire.');
        }

        $source = $this->logSourceRepository->findOneBy(['apiKey' => $normalizedApiKey, 'isActive' => true]);
        if (!$source instanceof LogSource) {
            throw new InvalidArgumentException('Source introuvable ou inactive pour cette api key.');
        }

        $now = time();
        $expiresIn = self::DEFAULT_TTL_SECONDS;

        $token = $this->jwtEncoder->encode([
            'sourceApiKey' => $source->getApiKey(),
            'sourceId' => $source->getId(),
            'sourceName' => $source->getName(),
            'sourceType' => $source->getType(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $expiresIn,
        ]);

        return [
            'accessToken' => $token,
            'expiresIn' => $expiresIn,
        ];
    }
}

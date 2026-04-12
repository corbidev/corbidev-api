<?php

namespace App\RessAuth\Security;

use App\RessAuth\Entity\AuthCredential;
use App\RessAuth\Repository\AuthCredentialRepository;
use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class AccessTokenIssuer implements AccessTokenIssuerInterface
{
    private const DEFAULT_TTL_SECONDS = 3600;

    public function __construct(
        private readonly AuthCredentialRepository $authCredentialRepository,
        private readonly JWTEncoderInterface $jwtEncoder,
    ) {
    }

    public function issueFromCredentials(string $sourceApiKey, string $clientSecret): array
    {
        $normalizedApiKey = trim($sourceApiKey);

        $credential = $this->authCredentialRepository->findActiveOneBySourceApiKey($normalizedApiKey);

        if (!$credential instanceof AuthCredential) {
            password_verify($clientSecret, '$argon2id$v=19$m=65536,t=4,p=1$dummysaltdummysalt$dummyhashvaluedummyhashvaluedummyhashvalue');
            throw new InvalidArgumentException('Identifiants invalides.');
        }

        $storedHash = $credential->getClientSecretHash();
        if ($storedHash === null || $storedHash === '') {
            throw new InvalidArgumentException('Aucun secret configure pour cette source. Contactez l\'administrateur.');
        }

        if (!password_verify($clientSecret, $storedHash)) {
            throw new InvalidArgumentException('Identifiants invalides.');
        }

        return $this->issueForSource($credential);
    }

    /**
     * @return array{accessToken: string, expiresIn: int}
     */
    private function issueForSource(AuthCredential $source): array
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
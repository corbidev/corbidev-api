<?php

namespace App\RessAuth\Security;

use App\RessAuth\Entity\AuthCredential;
use App\RessAuth\RessAuthConstants;
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
            password_verify($clientSecret, RessAuthConstants::DUMMY_PASSWORD_HASH);
            throw new InvalidArgumentException(RessAuthConstants::ERROR_INVALID_CREDENTIALS);
        }

        $storedHash = $credential->getClientSecretHash();
        if ($storedHash === null || $storedHash === '') {
            throw new InvalidArgumentException(RessAuthConstants::ERROR_SECRET_NOT_CONFIGURED);
        }

        if (!password_verify($clientSecret, $storedHash)) {
            throw new InvalidArgumentException(RessAuthConstants::ERROR_INVALID_CREDENTIALS);
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
            RessAuthConstants::JWT_CLAIM_SOURCE_API_KEY => $source->getApiKey(),
            RessAuthConstants::JWT_CLAIM_SOURCE_ID => $source->getId(),
            RessAuthConstants::JWT_CLAIM_SOURCE_NAME => $source->getName(),
            RessAuthConstants::JWT_CLAIM_SOURCE_TYPE => $source->getType(),
            RessAuthConstants::JWT_CLAIM_ISSUED_AT => $now,
            RessAuthConstants::JWT_CLAIM_EXPIRES_AT => $now + $expiresIn,
        ]);

        return [
            RessAuthConstants::PAYLOAD_KEY_ACCESS_TOKEN => $token,
            RessAuthConstants::PAYLOAD_KEY_EXPIRES_IN => $expiresIn,
        ];
    }
}
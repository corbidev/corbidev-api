<?php

namespace App\RessLogs\Security;

use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class LogConsumerJwtResolver implements LogConsumerJwtResolverInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtTokenManager,
    ) {
    }

    /**
     * @return array{sourceApiKey: ?string, sourceId: ?int}
     */
    public function resolveSourceContextFromRequest(Request $request): array
    {
        $authorization = $request->headers->get('Authorization');
        if (!is_string($authorization) || trim($authorization) === '') {
            return [
                'sourceApiKey' => null,
                'sourceId' => null,
            ];
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw new InvalidArgumentException('Le header Authorization doit utiliser le schéma Bearer.');
        }

        $jwt = trim($matches[1]);
        if ($jwt === '') {
            throw new InvalidArgumentException('Le token JWT est vide.');
        }

        try {
            $payload = $this->jwtTokenManager->parse($jwt);
        } catch (JWTDecodeFailureException) {
            throw new InvalidArgumentException('Token JWT invalide ou expiré.');
        }

        $sourceApiKey = null;
        foreach (['sourceApiKey', 'source_api_key', 'apiKey', 'api_key'] as $claimName) {
            $claim = $payload[$claimName] ?? null;
            if (is_string($claim) && trim($claim) !== '') {
                $sourceApiKey = trim($claim);
                break;
            }
        }

        $sourceId = null;
        foreach (['sourceId', 'source_id'] as $claimName) {
            $claim = $payload[$claimName] ?? null;
            if (is_int($claim) && $claim > 0) {
                $sourceId = $claim;
                break;
            }

            if (is_string($claim) && ctype_digit($claim) && (int) $claim > 0) {
                $sourceId = (int) $claim;
                break;
            }
        }

        return [
            'sourceApiKey' => $sourceApiKey,
            'sourceId' => $sourceId,
        ];
    }

    public function resolveSourceApiKeyFromRequest(Request $request): ?string
    {
        $context = $this->resolveSourceContextFromRequest($request);

        return $context['sourceApiKey'];
    }
}

<?php

namespace App\RessAuth\Security;

use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class AccessTokenResolver implements AccessTokenResolverInterface
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtTokenManager,
    ) {
    }

    public function verifyRequest(Request $request): AccessTokenContext
    {
        $authorization = $request->headers->get('Authorization');
        if (!is_string($authorization) || trim($authorization) === '') {
            return new AccessTokenContext(null, null);
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw new InvalidArgumentException('Le header Authorization doit utiliser le schema Bearer.');
        }

        $jwt = trim($matches[1]);
        if ($jwt === '') {
            throw new InvalidArgumentException('Le token JWT est vide.');
        }

        try {
            $payload = $this->jwtTokenManager->parse($jwt);
        } catch (JWTDecodeFailureException) {
            throw new InvalidArgumentException('Token JWT invalide ou expire.');
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

        return new AccessTokenContext($sourceApiKey, $sourceId);
    }

    public function requireRequest(Request $request): AccessTokenContext
    {
        $context = $this->verifyRequest($request);

        if ($context->sourceApiKey === null && $context->sourceId === null) {
            throw new InvalidArgumentException('Le header Authorization avec un Bearer JWT valide est obligatoire.');
        }

        return $context;
    }

    public function resolveSourceApiKeyFromRequest(Request $request): ?string
    {
        $context = $this->verifyRequest($request);

        return $context->sourceApiKey;
    }
}
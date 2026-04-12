<?php

namespace App\RessAuth\Security;

use App\RessAuth\Dto\AccessTokenContext;
use App\RessAuth\RessAuthConstants;
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
        $authorization = $request->headers->get(RessAuthConstants::AUTHORIZATION_HEADER);
        if (!is_string($authorization) || trim($authorization) === '') {
            return new AccessTokenContext(null, null);
        }

        if (!preg_match(RessAuthConstants::BEARER_TOKEN_PATTERN, $authorization, $matches)) {
            throw new InvalidArgumentException(RessAuthConstants::ERROR_AUTH_HEADER_SCHEME);
        }

        $jwt = trim($matches[1]);
        if ($jwt === '') {
            throw new InvalidArgumentException(RessAuthConstants::ERROR_EMPTY_JWT);
        }

        try {
            $payload = $this->jwtTokenManager->parse($jwt);
        } catch (JWTDecodeFailureException) {
            throw new InvalidArgumentException(RessAuthConstants::ERROR_INVALID_OR_EXPIRED_JWT);
        }

        $sourceApiKey = null;
        foreach (RessAuthConstants::SOURCE_CLAIMS as $claimName) {
            $claim = $payload[$claimName] ?? null;
            if (is_string($claim) && trim($claim) !== '') {
                $sourceApiKey = trim($claim);
                break;
            }
        }

        $sourceId = null;
        foreach (RessAuthConstants::SOURCE_ID_CLAIMS as $claimName) {
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
            throw new InvalidArgumentException(RessAuthConstants::ERROR_AUTH_HEADER_REQUIRED);
        }

        return $context;
    }

    public function resolveSourceApiKeyFromRequest(Request $request): ?string
    {
        $context = $this->verifyRequest($request);

        return $context->sourceApiKey;
    }
}
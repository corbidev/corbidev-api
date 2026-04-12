<?php

namespace App\RessAuth\Controller;

use App\RessAuth\RessAuthConstants;
use App\RessAuth\Security\AccessTokenIssuerInterface;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CreateAccessTokenController
{
    public function __construct(
        private readonly AccessTokenIssuerInterface $accessTokenIssuer,
    ) {
    }

    #[Route(RessAuthConstants::TOKEN_PATH, name: RessAuthConstants::TOKEN_ROUTE, methods: ['POST'])]
    #[Route(RessAuthConstants::LEGACY_TOKEN_PATH, name: RessAuthConstants::LEGACY_TOKEN_ROUTE, methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse([
                RessAuthConstants::RESPONSE_KEY_ERROR => RessAuthConstants::ERROR_JSON_INVALID,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!is_array($payload)) {
            return new JsonResponse([
                RessAuthConstants::RESPONSE_KEY_ERROR => RessAuthConstants::ERROR_JSON_OBJECT_REQUIRED,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $sourceApiKey = $payload[RessAuthConstants::PAYLOAD_KEY_SOURCE_API_KEY] ?? null;
        $clientSecret = $payload[RessAuthConstants::PAYLOAD_KEY_CLIENT_SECRET] ?? null;

        if (!is_string($sourceApiKey) || '' === trim($sourceApiKey)) {
            return new JsonResponse([
                RessAuthConstants::RESPONSE_KEY_ERROR => RessAuthConstants::ERROR_SOURCE_API_KEY_REQUIRED,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!is_string($clientSecret) || '' === trim($clientSecret)) {
            return new JsonResponse([
                RessAuthConstants::RESPONSE_KEY_ERROR => RessAuthConstants::ERROR_CLIENT_SECRET_REQUIRED,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $tokenPayload = $this->accessTokenIssuer->issueFromCredentials($sourceApiKey, $clientSecret);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                RessAuthConstants::RESPONSE_KEY_ERROR => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            RessAuthConstants::RESPONSE_KEY_TOKEN_TYPE => RessAuthConstants::TOKEN_TYPE_BEARER,
            RessAuthConstants::RESPONSE_KEY_ACCESS_TOKEN => $tokenPayload->accessToken,
            RessAuthConstants::RESPONSE_KEY_EXPIRES_IN => $tokenPayload->expiresIn,
        ], JsonResponse::HTTP_CREATED);
    }
}
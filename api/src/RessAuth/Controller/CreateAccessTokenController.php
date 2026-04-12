<?php

namespace App\RessAuth\Controller;

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

    #[Route('/api/auth/token', name: 'api_auth_token_create', methods: ['POST'])]
    #[Route('/api/logs/token', name: 'api_logs_token_create_legacy', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse([
                'error' => 'JSON invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!is_array($payload)) {
            return new JsonResponse([
                'error' => 'Le corps de la requête doit etre un objet JSON.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $sourceApiKey = $payload['sourceApiKey'] ?? null;
        $clientSecret = $payload['clientSecret'] ?? null;

        if (!is_string($sourceApiKey) || '' === trim($sourceApiKey)) {
            return new JsonResponse([
                'error' => 'Le champ "sourceApiKey" est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!is_string($clientSecret) || '' === trim($clientSecret)) {
            return new JsonResponse([
                'error' => 'Le champ "clientSecret" est obligatoire.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $tokenPayload = $this->accessTokenIssuer->issueFromCredentials($sourceApiKey, $clientSecret);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                'error' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'token_type' => 'Bearer',
            'access_token' => $tokenPayload['accessToken'],
            'expires_in' => $tokenPayload['expiresIn'],
        ], JsonResponse::HTTP_CREATED);
    }
}
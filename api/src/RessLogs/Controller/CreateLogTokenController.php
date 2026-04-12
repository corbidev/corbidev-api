<?php

namespace App\RessLogs\Controller;

use App\RessLogs\Security\LogConsumerJwtIssuerInterface;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CreateLogTokenController
{
    public function __construct(
        private readonly LogConsumerJwtIssuerInterface $logConsumerJwtIssuer,
    ) {
    }

    #[Route('/api/logs/token', name: 'api_logs_token_create', methods: ['POST'])]
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
                'error' => 'Le corps de la requête doit être un objet JSON.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $sourceApiKey = $payload['sourceApiKey'] ?? $request->headers->get('x-api-key');

        try {
            $tokenPayload = $this->logConsumerJwtIssuer->issueFromSourceApiKey((string) $sourceApiKey);
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

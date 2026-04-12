<?php

namespace App\RessLogs\Controller;

use App\RessLogs\Mapper\CreateLogRequestMapperInterface;
use App\RessLogs\Security\LogConsumerJwtResolverInterface;
use App\RessLogs\Service\LogRecorderInterface;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CreateLogController
{
    public function __construct(
        private readonly CreateLogRequestMapperInterface $createLogRequestMapper,
        private readonly LogConsumerJwtResolverInterface $logConsumerJwtResolver,
        private readonly LogRecorderInterface $logRecorder,
    ) {
    }

    #[Route('/api/logs', name: 'api_logs_create', methods: ['POST'])]
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

        try {
            $sourceContext = $this->logConsumerJwtResolver->resolveSourceContextFromRequest($request);
            $apiKey = $sourceContext['sourceApiKey'];

            if (!isset($payload['sourceId']) && is_int($sourceContext['sourceId'])) {
                $payload['sourceId'] = $sourceContext['sourceId'];
            }

            $requestDto = $this->createLogRequestMapper->map($payload, is_string($apiKey) ? $apiKey : null);
            $entry = $this->logRecorder->record($requestDto);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                'error' => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'id' => $entry->getId(),
            'status' => 'created',
        ], JsonResponse::HTTP_CREATED);
    }
}

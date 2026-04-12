<?php

namespace App\RessLogs\Controller;

use App\RessLogs\Mapper\CreateLogRequestMapperInterface;
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

        $apiKey = $request->headers->get('x-api-key') ?? $request->headers->get('authorization');
        if (is_string($apiKey) && str_starts_with($apiKey, 'Bearer ')) {
            $apiKey = trim(substr($apiKey, 7));
        }

        try {
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
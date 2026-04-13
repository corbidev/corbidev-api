<?php

namespace App\RessLogs\Controller;

use App\RessAuth\Security\InvalidAccessTokenException;
use App\RessAuth\Security\AccessTokenResolverInterface;
use App\RessLogs\Mapper\CreateLogRequestMapperInterface;
use App\RessLogs\RessLogsConstants;
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
        private readonly AccessTokenResolverInterface $accessTokenResolver,
        private readonly LogRecorderInterface $logRecorder,
    ) {
    }

    #[Route(RessLogsConstants::LOGS_PATH, name: RessLogsConstants::LOGS_ROUTE, methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse([
                RessLogsConstants::RESPONSE_KEY_ERROR => RessLogsConstants::ERROR_JSON_INVALID,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!is_array($payload)) {
            return new JsonResponse([
                RessLogsConstants::RESPONSE_KEY_ERROR => RessLogsConstants::ERROR_JSON_OBJECT_REQUIRED,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $accessTokenContext = $this->accessTokenResolver->requireRequest($request);

            if (array_key_exists(RessLogsConstants::FIELD_SOURCE_API_KEY, $payload) || array_key_exists(RessLogsConstants::FIELD_SOURCE_ID, $payload)) {
                return new JsonResponse([
                    RessLogsConstants::RESPONSE_KEY_ERROR => RessLogsConstants::ERROR_SOURCE_FIELDS_FORBIDDEN,
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $apiKey = $accessTokenContext->sourceApiKey;

            $requestDto = $this->createLogRequestMapper->map(
                $payload,
                is_string($apiKey) ? $apiKey : null,
                is_int($accessTokenContext->sourceId) ? $accessTokenContext->sourceId : null,
            );
            $entry = $this->logRecorder->record($requestDto);
        } catch (InvalidAccessTokenException $exception) {
            return new JsonResponse([
                RessLogsConstants::RESPONSE_KEY_ERROR => $exception->getMessage(),
            ], JsonResponse::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                RessLogsConstants::RESPONSE_KEY_ERROR => $exception->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            RessLogsConstants::RESPONSE_KEY_ID => $entry->getId(),
            RessLogsConstants::RESPONSE_KEY_STATUS => RessLogsConstants::RESPONSE_STATUS_CREATED,
        ], JsonResponse::HTTP_CREATED);
    }
}
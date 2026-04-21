<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Domain\Error\ApiError;
use Symfony\Component\HttpFoundation\JsonResponse;

final class JsonResponseFactory
{
    public function error(ApiError $error, int $status): JsonResponse
    {
        return new JsonResponse(
            $error->toArray(),
            $status,
            [
                'Content-Type' => 'application/json'
            ]
        );
    }

    public function success(array $data = [], int $status = 200): JsonResponse
    {
        return new JsonResponse(
            [
                'success' => true,
                'data' => $data
            ],
            $status,
            [
                'Content-Type' => 'application/json'
            ]
        );
    }
}
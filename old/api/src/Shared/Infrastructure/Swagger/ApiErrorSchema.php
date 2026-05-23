<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ApiError",
    type: "object",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: false),
        new OA\Property(
            property: "error",
            type: "object",
            properties: [
                new OA\Property(
                    property: "code",
                    type: "string",
                    example: "RESOURCE_ALREADY_EXISTS"
                ),
                new OA\Property(
                    property: "business_code",
                    ref: "#/components/schemas/BusinessErrorCode"
                ),
                new OA\Property(
                    property: "message",
                    type: "string",
                    example: "Log already exists"
                ),
                new OA\Property(
                    property: "details",
                    type: "object",
                    additionalProperties: true
                )
            ]
        )
    ]
)]
final class ApiErrorSchema
{
}
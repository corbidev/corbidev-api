<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "BusinessErrorCatalog",
    type: "object",
    additionalProperties: new OA\Schema(
        type: "object",
        properties: [
            new OA\Property(property: "message", type: "string"),
            new OA\Property(property: "description", type: "string", nullable: true),
            new OA\Property(property: "http_status", type: "integer", nullable: true),
        ]
    ),
    example: [
        "LOG_001" => [
            "message" => "Log already exists",
            "description" => "A log with this externalId already exists",
            "http_status" => 409
        ]
    ]
)]
final class BusinessErrorCatalogSchema
{
}
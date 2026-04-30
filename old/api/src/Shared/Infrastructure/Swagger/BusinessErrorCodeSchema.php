<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "BusinessErrorCode",
    type: "string",
    description: "Business error code",
    enum: [
        "LOG_001",
        "LOG_002",
        "LOG_003",
        "USER_001",
        "AUTH_001",
        "AUTH_002",
        "GEN_001",
        "GEN_999"
    ],
    example: "LOG_001"
)]
final class BusinessErrorCodeSchema
{
}
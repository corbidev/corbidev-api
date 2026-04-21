<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Swagger;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\Error\BusinessErrorRegistry;

final class OpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        private readonly BusinessErrorRegistry $registry
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();

        // =========================
        // 🔹 ENUM dynamique
        // =========================
        if (isset($schemas['BusinessErrorCode'])) {
            $schema = $schemas['BusinessErrorCode'];

            $schema = $schema->withEnum(array_keys($this->registry->all()));

            $schemas['BusinessErrorCode'] = $schema;
        }

        // =========================
        // 🔹 Catalogue enrichi 🔥
        // =========================
        $schemas['BusinessErrorCatalog'] = new \ArrayObject([
            'type' => 'object',
            'additionalProperties' => [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'description' => ['type' => 'string', 'nullable' => true],
                    'http_status' => ['type' => 'integer', 'nullable' => true],
                ],
            ],
            'example' => $this->registry->allFormatted(),
        ]);

        return $openApi;
    }
}
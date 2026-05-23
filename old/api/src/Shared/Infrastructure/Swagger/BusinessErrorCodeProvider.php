<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Swagger;

use App\Shared\Infrastructure\Error\BusinessErrorRegistry;

final class BusinessErrorCodeProvider
{
    public function __construct(
        private readonly BusinessErrorRegistry $registry
    ) {}

    public function getCodes(): array
    {
        return array_keys($this->registry->all());
    }
}
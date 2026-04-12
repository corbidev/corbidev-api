<?php

namespace App\RessLogs\Mapper;

use App\RessLogs\Dto\CreateLogRequestDto;

interface CreateLogRequestMapperInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function map(array $payload, ?string $apiKey = null): CreateLogRequestDto;
}

<?php

namespace App\RessAuth\Dto;

final readonly class AccessTokenContext
{
    public function __construct(
        public ?string $sourceApiKey,
        public ?int $sourceId,
    ) {
    }
}
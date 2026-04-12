<?php

namespace App\RessAuth\Security;

final readonly class AccessTokenContext
{
    public function __construct(
        public ?string $sourceApiKey,
        public ?int $sourceId,
    ) {
    }
}
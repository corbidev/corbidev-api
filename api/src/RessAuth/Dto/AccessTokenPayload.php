<?php

namespace App\RessAuth\Dto;

final readonly class AccessTokenPayload
{
    public function __construct(
        public string $accessToken,
        public int $expiresIn,
    ) {
    }
}
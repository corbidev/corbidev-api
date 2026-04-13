<?php

namespace App\RessLogs\Dto;

use DateTimeImmutable;

final readonly class CreateLogRequestDto
{
    /**
     * @param array<string, mixed>|null $context
     * @param array<int|string>|null $tags
     */
    public function __construct(
        public string $message,
        public ?string $title,
        public ?string $url,
        public ?int $httpStatus,
        public ?int $durationMs,
        public ?string $fingerprint,
        public ?array $context,
        public DateTimeImmutable|string|null $ts,
        public DateTimeImmutable|string|null $createdAt,
        public int|string|null $level,
        public int|string|null $env,
        public ?int $sourceId,
        public ?string $sourceApiKey,
        public ?int $urlId,
        public ?int $uriId,
        public ?int $routeId,
        public ?string $uri,
        public ?array $tags,
    ) {
    }
}

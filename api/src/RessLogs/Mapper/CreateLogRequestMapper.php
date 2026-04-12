<?php

namespace App\RessLogs\Mapper;

use App\RessLogs\Dto\CreateLogRequestDto;

final class CreateLogRequestMapper implements CreateLogRequestMapperInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function map(array $payload, ?string $apiKey = null, ?int $sourceId = null): CreateLogRequestDto
    {
        $sourceApiKey = $apiKey !== null && trim($apiKey) !== '' ? trim($apiKey) : null;

        return new CreateLogRequestDto(
            message: trim((string) ($payload['message'] ?? '')),
            title: $this->nullableString($payload['title'] ?? null),
            url: $this->nullableString($payload['url'] ?? null),
            httpStatus: $this->nullableInt($payload['httpStatus'] ?? null),
            durationMs: $this->nullableInt($payload['durationMs'] ?? null),
            fingerprint: $this->nullableString($payload['fingerprint'] ?? null),
            context: is_array($payload['context'] ?? null) ? $payload['context'] : null,
            ts: $payload['ts'] ?? null,
            createdAt: $payload['createdAt'] ?? null,
            level: $this->nullableIntOrString($payload['level'] ?? null),
            env: $this->nullableIntOrString($payload['env'] ?? null),
            sourceId: $sourceId,
            sourceApiKey: $sourceApiKey,
            urlId: $this->nullableInt($payload['urlId'] ?? null),
            uriId: $this->nullableInt($payload['uriId'] ?? null),
            routeId: $this->nullableInt($payload['routeId'] ?? null),
            uri: $this->nullableString($payload['uri'] ?? null),
            tags: is_array($payload['tags'] ?? null) ? $payload['tags'] : null,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableIntOrString(mixed $value): int|string|null
    {
        if (is_int($value)) {
            return $value;
        }

        $stringValue = $this->nullableString($value);
        if ($stringValue === null) {
            return null;
        }

        return ctype_digit($stringValue) ? (int) $stringValue : $stringValue;
    }
}

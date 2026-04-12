<?php

namespace App\RessLogs\Mapper;

use App\RessLogs\Dto\CreateLogRequestDto;
use App\RessLogs\RessLogsConstants;

final class CreateLogRequestMapper implements CreateLogRequestMapperInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function map(array $payload, ?string $apiKey = null, ?int $sourceId = null): CreateLogRequestDto
    {
        $sourceApiKey = $apiKey !== null && trim($apiKey) !== RessLogsConstants::EMPTY_STRING ? trim($apiKey) : null;

        return new CreateLogRequestDto(
            message: trim((string) ($payload[RessLogsConstants::FIELD_MESSAGE] ?? RessLogsConstants::EMPTY_STRING)),
            title: $this->nullableString($payload[RessLogsConstants::FIELD_TITLE] ?? null),
            url: $this->nullableString($payload[RessLogsConstants::FIELD_URL] ?? null),
            httpStatus: $this->nullableInt($payload[RessLogsConstants::FIELD_HTTP_STATUS] ?? null),
            durationMs: $this->nullableInt($payload[RessLogsConstants::FIELD_DURATION_MS] ?? null),
            fingerprint: $this->nullableString($payload[RessLogsConstants::FIELD_FINGERPRINT] ?? null),
            context: is_array($payload[RessLogsConstants::FIELD_CONTEXT] ?? null) ? $payload[RessLogsConstants::FIELD_CONTEXT] : null,
            ts: $payload[RessLogsConstants::FIELD_TS] ?? null,
            createdAt: $payload[RessLogsConstants::FIELD_CREATED_AT] ?? null,
            level: $this->nullableIntOrString($payload[RessLogsConstants::FIELD_LEVEL] ?? null),
            env: $this->nullableIntOrString($payload[RessLogsConstants::FIELD_ENV] ?? null),
            sourceId: $sourceId,
            sourceApiKey: $sourceApiKey,
            urlId: $this->nullableInt($payload[RessLogsConstants::FIELD_URL_ID] ?? null),
            uriId: $this->nullableInt($payload[RessLogsConstants::FIELD_URI_ID] ?? null),
            routeId: $this->nullableInt($payload[RessLogsConstants::FIELD_ROUTE_ID] ?? null),
            uri: $this->nullableString($payload[RessLogsConstants::FIELD_URI] ?? null),
            tags: is_array($payload[RessLogsConstants::FIELD_TAGS] ?? null) ? $payload[RessLogsConstants::FIELD_TAGS] : null,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        return $stringValue === RessLogsConstants::EMPTY_STRING ? null : $stringValue;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === RessLogsConstants::EMPTY_STRING) {
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

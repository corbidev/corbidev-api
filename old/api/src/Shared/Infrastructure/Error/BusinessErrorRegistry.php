<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Error;

use App\Shared\Domain\Error\BusinessErrorCode;

final class BusinessErrorRegistry
{
    private array $errors;

    public function __construct(array $business_errors)
    {
        $this->errors = $business_errors;
    }

    // =========================
    // 🔹 Accès brut (Swagger enum)
    // =========================
    public function all(): array
    {
        return $this->errors;
    }

    // =========================
    // 🔹 Accès enrichi (Swagger avancé)
    // =========================
    public function allFormatted(): array
    {
        $formatted = [];

        foreach ($this->errors as $code => $data) {
            $formatted[$code] = [
                'message' => $data['message'] ?? 'Unknown error',
                'description' => $data['description'] ?? null,
                'http_status' => $data['http_status'] ?? null,
            ];
        }

        return $formatted;
    }

    public function get(BusinessErrorCode $code): array
    {
        $key = $code->value;

        if (!isset($this->errors[$key])) {
            return $this->getFallback($code);
        }

        return $this->errors[$key];
    }

    public function getMessage(BusinessErrorCode $code): string
    {
        return $this->get($code)['message'] ?? 'Unknown error';
    }

    public function getDescription(BusinessErrorCode $code): ?string
    {
        return $this->get($code)['description'] ?? null;
    }

    public function getHttpStatus(BusinessErrorCode $code): ?int
    {
        return $this->get($code)['http_status'] ?? null;
    }

    public function exists(BusinessErrorCode $code): bool
    {
        return isset($this->errors[$code->value]);
    }

    private function getFallback(BusinessErrorCode $code): array
    {
        return [
            'code' => $code->name,
            'message' => 'Unknown business error',
            'description' => null,
            'http_status' => null,
        ];
    }
}
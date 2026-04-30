<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

final class ApiError
{
    public function __construct(
        private readonly ErrorCode $code,
        private readonly string $message,
        private readonly array $details = [],
        private readonly ?BusinessErrorCode $businessCode = null
    ) {
    }

    public function getCode(): ErrorCode
    {
        return $this->code;
    }

    public function getBusinessCode(): ?BusinessErrorCode
    {
        return $this->businessCode;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $this->code->value,
                'business_code' => $this->businessCode?->value,
                'message' => $this->message,
                'details' => $this->details,
            ],
        ];
    }

    // =========================
    // 🏭 Factories utiles
    // =========================

    public static function validation(array $errors): self
    {
        return new self(
            ErrorCode::VALIDATION_ERROR,
            'Validation failed',
            $errors,
            BusinessErrorCode::VALIDATION_FAILED
        );
    }

    public static function domain(
        string $message,
        array $details = [],
        ?BusinessErrorCode $businessCode = null
    ): self {
        return new self(
            ErrorCode::DOMAIN_ERROR,
            $message,
            $details,
            $businessCode
        );
    }

    public static function notFound(string $message = 'Resource not found'): self
    {
        return new self(
            ErrorCode::RESOURCE_NOT_FOUND,
            $message
        );
    }

    public static function alreadyExists(
        string $message = 'Resource already exists',
        ?BusinessErrorCode $businessCode = null
    ): self {
        return new self(
            ErrorCode::RESOURCE_ALREADY_EXISTS,
            $message,
            [],
            $businessCode
        );
    }

    public static function database(string $message = 'Database error'): self
    {
        return new self(
            ErrorCode::DATABASE_ERROR,
            $message
        );
    }

    public static function unknown(): self
    {
        return new self(
            ErrorCode::UNKNOWN_ERROR,
            'An unexpected error occurred',
            [],
            BusinessErrorCode::UNKNOWN_ERROR
        );
    }
}
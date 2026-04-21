<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

use RuntimeException;

class DomainException extends RuntimeException
{
    public function __construct(
        private readonly ErrorCode $errorCode,
        string $message = '',
        private readonly array $details = [],
        private readonly ?string $businessCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getBusinessCode(): ?string
    {
        return $this->businessCode;
    }

    // =========================
    // 🏭 Factories métier
    // =========================

    public static function notFound(string $message = 'Resource not found', ?string $businessCode = null): self
    {
        return new self(ErrorCode::RESOURCE_NOT_FOUND, $message, [], $businessCode);
    }

    public static function alreadyExists(string $message = 'Resource already exists', ?string $businessCode = null): self
    {
        return new self(ErrorCode::RESOURCE_ALREADY_EXISTS, $message, [], $businessCode);
    }

    public static function validation(array $details, string $message = 'Validation failed', ?string $businessCode = null): self
    {
        return new self(ErrorCode::VALIDATION_ERROR, $message, $details, $businessCode);
    }

    public static function database(string $message = 'Database error', ?string $businessCode = null): self
    {
        return new self(ErrorCode::DATABASE_ERROR, $message, [], $businessCode);
    }

    public static function generic(string $message = 'Domain error', ?string $businessCode = null): self
    {
        return new self(ErrorCode::DOMAIN_ERROR, $message, [], $businessCode);
    }
}
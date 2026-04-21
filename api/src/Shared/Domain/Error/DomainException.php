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
        private readonly ?BusinessErrorCode $businessCode = null,
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

    public function getBusinessCode(): ?BusinessErrorCode
    {
        return $this->businessCode;
    }

    // =========================
    // 🏭 Factories métier
    // =========================

    public static function notFound(
        string $message = 'Resource not found',
        ?BusinessErrorCode $businessCode = null
    ): self {
        return new self(
            ErrorCode::RESOURCE_NOT_FOUND,
            $message,
            [],
            $businessCode
        );
    }

    public static function alreadyExists(
        string $message = 'Resource already exists',
        array $details = [],
        ?BusinessErrorCode $businessCode = null
    ): self {
        return new self(
            ErrorCode::RESOURCE_ALREADY_EXISTS,
            $message,
            $details,
            $businessCode
        );
    }

    public static function validation(
        array $details,
        string $message = 'Invalid input',
        ?BusinessErrorCode $businessCode = BusinessErrorCode::VALIDATION_FAILED
    ): self {
        return new self(
            ErrorCode::VALIDATION_ERROR,
            $message,
            $details,
            $businessCode
        );
    }

    public static function database(
        string $message = 'Database error'
    ): self {
        return new self(
            ErrorCode::DATABASE_ERROR,
            $message
        );
    }

    public static function generic(
        string $message = 'Domain error',
        ?BusinessErrorCode $businessCode = null
    ): self {
        return new self(
            ErrorCode::DOMAIN_ERROR,
            $message,
            [],
            $businessCode
        );
    }
}
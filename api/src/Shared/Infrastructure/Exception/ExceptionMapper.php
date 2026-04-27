<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Exception;

use App\Shared\Domain\Error\ApiError;
use App\Shared\Domain\Error\DomainException;
use App\Shared\Domain\Error\ErrorCode;
use App\Shared\Domain\Error\BusinessErrorCode;
use App\Shared\Infrastructure\Error\BusinessErrorRegistry;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use ApiPlatform\Validator\Exception\ValidationException as ApiValidationException;

final class ExceptionMapper
{
    public function __construct(
        private readonly BusinessErrorRegistry $registry
    ) {}

    // =========================
    // 🔥 retourne error + status
    // =========================
    public function mapWithStatus(\Throwable $exception): array
    {
        $error = $this->map($exception);
        $status = $this->resolveHttpStatus($error);

        return [$error, $status];
    }

    public function map(\Throwable $exception): ApiError
    {
        // =========================
        // 📥 Validation Symfony
        // =========================
        if ($exception instanceof ValidationFailedException) {
            return $this->validationError(
                $this->formatViolations($exception->getViolations())
            );
        }

        // =========================
        // 📥 Validation API Platform
        // =========================
        if ($exception instanceof ApiValidationException) {
            return $this->validationError(
                $this->formatViolations($exception->getConstraintViolationList())
            );
        }

        // =========================
        // 🧠 Domaine
        // =========================
        if ($exception instanceof DomainException) {
            return $this->domainError($exception);
        }

        // =========================
        // 🌐 HTTP Symfony
        // =========================
        if ($exception instanceof HttpExceptionInterface) {
            return $this->mapHttpException($exception);
        }

        // =========================
        // ❌ Fallback (infra / DB / inconnue)
        // =========================
        return $this->businessError(
            ErrorCode::UNKNOWN_ERROR,
            BusinessErrorCode::UNKNOWN_ERROR
        );
    }

    // =========================
    // 🧠 Domain Exception
    // =========================
    private function domainError(DomainException $exception): ApiError
    {
        $businessCode = $exception->getBusinessCode();

        $message = $exception->getMessage();

        if ($businessCode && $this->registry->exists($businessCode)) {
            $message = $this->registry->getMessage($businessCode);
        }

        return new ApiError(
            $exception->getErrorCode(),
            $message,
            $exception->getDetails(),
            $businessCode
        );
    }

    // =========================
    // 📥 Validation
    // =========================
    private function validationError(array $details): ApiError
    {
        $code = BusinessErrorCode::VALIDATION_FAILED;

        return new ApiError(
            ErrorCode::VALIDATION_ERROR,
            $this->registry->getMessage($code),
            $details,
            $code
        );
    }

    // =========================
    // 🧩 Generic Business Error
    // =========================
    private function businessError(ErrorCode $errorCode, BusinessErrorCode $businessCode): ApiError
    {
        return new ApiError(
            $errorCode,
            $this->registry->getMessage($businessCode),
            [],
            $businessCode
        );
    }

    // =========================
    // 🧩 Format des violations
    // =========================
    private function formatViolations(iterable $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();

            $errors[$field][] = [
                'code' => $this->mapConstraintToCode($violation->getCode()),
                'message' => $violation->getMessage(),
            ];
        }

        return $errors;
    }

    // =========================
    // 🔁 Constraint → code
    // =========================
    private function mapConstraintToCode(?string $constraintCode): string
    {
        return match ($constraintCode) {
            'c1051bb4-d103-4f74-8988-acbcafc7fdc3' => 'REQUIRED',
            'bd79c0ab-ddba-46cc-a703-a7a4b08de310' => 'INVALID_EMAIL',
            '9ff3fdc4-b214-49db-8718-39c315e33d45' => 'INVALID_LENGTH',
            '2e35a97b-63c6-4d27-8f3c-9b4a4f8d84d9' => 'INVALID_IP',
            default => 'INVALID',
        };
    }

    // =========================
    // 🌐 HTTP Symfony
    // =========================
    private function mapHttpException(HttpExceptionInterface $exception): ApiError
    {
        return match ($exception->getStatusCode()) {
            404 => new ApiError(ErrorCode::RESOURCE_NOT_FOUND, 'Resource not found'),
            403 => new ApiError(ErrorCode::FORBIDDEN, 'Access denied'),
            401 => new ApiError(ErrorCode::UNAUTHORIZED, 'Unauthorized'),
            405 => new ApiError(ErrorCode::METHOD_NOT_ALLOWED, 'Method not allowed'),
            default => new ApiError(
                ErrorCode::UNKNOWN_ERROR,
                $exception->getMessage() ?: 'HTTP error'
            ),
        };
    }

    // =========================
    // 🔥 HTTP depuis YAML
    // =========================
    private function resolveHttpStatus(ApiError $error): int
    {
        $businessCode = $error->getBusinessCode();

        if ($businessCode && $this->registry->exists($businessCode)) {
            $status = $this->registry->getHttpStatus($businessCode);

            if ($status) {
                return $status;
            }
        }

        return match ($error->getCode()) {
            ErrorCode::VALIDATION_ERROR => 400,
            ErrorCode::RESOURCE_NOT_FOUND => 404,
            ErrorCode::RESOURCE_ALREADY_EXISTS => 409,
            ErrorCode::UNAUTHORIZED => 401,
            ErrorCode::FORBIDDEN => 403,
            default => 500,
        };
    }
}
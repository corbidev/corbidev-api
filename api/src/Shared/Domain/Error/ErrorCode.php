<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

enum ErrorCode: string
{
    // =========================
    // 🔧 Générique
    // =========================
    case UNKNOWN_ERROR = 'UNKNOWN_ERROR';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';

    // =========================
    // 📥 Validation
    // =========================
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case INVALID_INPUT = 'INVALID_INPUT';
    case MISSING_FIELD = 'MISSING_FIELD';

    // =========================
    // 🧠 Domaine
    // =========================
    case DOMAIN_ERROR = 'DOMAIN_ERROR';
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    case RESOURCE_ALREADY_EXISTS = 'RESOURCE_ALREADY_EXISTS';

    // =========================
    // 🔐 Auth / Sécurité
    // =========================
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';

    // =========================
    // 🗄️ Infrastructure
    // =========================
    case DATABASE_ERROR = 'DATABASE_ERROR';
    case UNIQUE_CONSTRAINT_VIOLATION = 'UNIQUE_CONSTRAINT_VIOLATION';

    // =========================
    // 🌐 HTTP
    // =========================
    case NOT_FOUND = 'NOT_FOUND';
    case METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
}
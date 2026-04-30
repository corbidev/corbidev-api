<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

enum BusinessErrorCode: string
{
    // =========================
    // 🧠 LOG DOMAIN
    // =========================
    case LOG_ALREADY_EXISTS = 'LOG_001';
    case LOG_INVALID_LEVEL = 'LOG_002';
    case LOG_INVALID_ENV = 'LOG_003';

    // =========================
    // 👤 USER DOMAIN
    // =========================
    case USER_NOT_FOUND = 'USER_001';

    // =========================
    // 🔐 AUTH DOMAIN
    // =========================
    case AUTH_INVALID_TOKEN = 'AUTH_001';
    case AUTH_EXPIRED_TOKEN = 'AUTH_002';

    // =========================
    // 🌐 GLOBAL
    // =========================
    case VALIDATION_FAILED = 'GEN_001';
    case UNKNOWN_ERROR = 'GEN_999';

    // =========================
    // 🧠 Helpers
    // =========================

    public function domain(): string
    {
        return explode('_', $this->value)[0];
    }
}
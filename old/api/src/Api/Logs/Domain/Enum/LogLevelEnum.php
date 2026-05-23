<?php

namespace App\Api\Logs\Domain\Enum;

enum LogLevelEnum: string
{
    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case NOTICE = 'NOTICE';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';
    case ALERT = 'ALERT';
    case EMERGENCY = 'EMERGENCY';

    public function severity(): int
    {
        return match ($this) {
            self::DEBUG => 100,
            self::INFO => 200,
            self::NOTICE => 250,
            self::WARNING => 300,
            self::ERROR => 400,
            self::CRITICAL => 500,
            self::ALERT => 550,
            self::EMERGENCY => 600,
        };
    }

    public static function fromString(string $value): self
    {
        return self::from(strtoupper($value));
    }
}
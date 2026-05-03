<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Logging\Emergency;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\PhpErrorLoggerInterface;

/**
 * Tests du EmergencyLogger.
 *
 * Objectif :
 * Garantir que le logger de secours :
 * - écrit toujours via le logger PHP
 * - ne lève jamais d'exception
 * - applique un format minimal stable
 *
 * Pourquoi :
 * EmergencyLogger est le dernier rempart du système.
 * Il ne doit JAMAIS échouer, même si tout le reste est cassé.
 */
final class EmergencyLoggerTest extends TestCase
{
    public function test_it_logs_message_with_emergency_prefix(): void
    {
        $phpLogger = $this->createMock(PhpErrorLoggerInterface::class);

        $phpLogger
            ->expects($this->once())
            ->method('log')
            ->with('[EMERGENCY] failure');

        $logger = new EmergencyLogger($phpLogger);

        $logger->log('failure');
    }

    public function test_it_accepts_empty_message(): void
    {
        $phpLogger = $this->createMock(PhpErrorLoggerInterface::class);

        $phpLogger
            ->expects($this->once())
            ->method('log')
            ->with('[EMERGENCY] ');

        $logger = new EmergencyLogger($phpLogger);

        $logger->log('');
    }

    public function test_it_never_throws_even_if_php_logger_fails(): void
    {
        $phpLogger = $this->createMock(PhpErrorLoggerInterface::class);

        $phpLogger
            ->expects($this->once())
            ->method('log')
            ->willThrowException(new \RuntimeException('failure'));

        $logger = new EmergencyLogger($phpLogger);

        $thrown = false;

        try {
            $logger->log('critical');
        } catch (\Throwable) {
            $thrown = true;
        }

        // ✔ vraie assertion → plus de notice PHPUnit
        $this->assertFalse($thrown);
    }

    public function test_it_logs_special_characters(): void
    {
        $message = 'Erreur critique: échec système 🚨';

        $phpLogger = $this->createMock(PhpErrorLoggerInterface::class);

        $phpLogger
            ->expects($this->once())
            ->method('log')
            ->with('[EMERGENCY] ' . $message);

        $logger = new EmergencyLogger($phpLogger);

        $logger->log($message);
    }
}
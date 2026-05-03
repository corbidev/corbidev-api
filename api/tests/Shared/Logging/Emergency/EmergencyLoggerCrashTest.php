<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Logging\Emergency;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\PhpErrorLoggerInterface;

/**
 * Tests de résilience du EmergencyLogger.
 *
 * Objectif :
 * Vérifier que le logger :
 * - n'explose jamais
 * - tente toujours de logger
 * - reste stable même si tout échoue
 */
final class EmergencyLoggerCrashTest extends TestCase
{
    public function test_it_survives_php_logger_crash(): void
    {
        $phpLogger = $this->createMock(PhpErrorLoggerInterface::class);

        // ✔ on vérifie que le logger est bien appelé
        $phpLogger
            ->expects($this->once())
            ->method('log')
            ->willThrowException(new \Error('fatal error'));

        $logger = new EmergencyLogger($phpLogger);

        // ✔ ne doit jamais throw
        $logger->log('system failure');

        $this->assertTrue(true); // ✔ assertion explicite
    }

    public function test_it_survives_runtime_exception(): void
    {
        $phpLogger = $this->createMock(PhpErrorLoggerInterface::class);

        $phpLogger
            ->expects($this->once())
            ->method('log')
            ->willThrowException(new \RuntimeException('boom'));

        $logger = new EmergencyLogger($phpLogger);

        $logger->log('unexpected');

        $this->assertTrue(true);
    }

    public function test_it_handles_very_large_message(): void
    {
        $largeMessage = str_repeat('A', 1_000_000);

        $phpLogger = $this->createMock(PhpErrorLoggerInterface::class);

        $phpLogger
            ->expects($this->once())
            ->method('log')
            ->with('[EMERGENCY] ' . $largeMessage);

        $logger = new EmergencyLogger($phpLogger);

        $logger->log($largeMessage);

        $this->assertTrue(true);
    }

    public function test_it_handles_binary_like_input(): void
    {
        $message = "\x00\x01\x02INVALID\xFF";

        $phpLogger = $this->createMock(PhpErrorLoggerInterface::class);

        $phpLogger
            ->expects($this->once())
            ->method('log')
            ->with('[EMERGENCY] ' . $message);

        $logger = new EmergencyLogger($phpLogger);

        $logger->log($message);

        $this->assertTrue(true);
    }

    public function test_it_never_throws_in_worst_case_scenario(): void
    {
        $phpLogger = $this->createMock(PhpErrorLoggerInterface::class);

        // 🔥 tout casse
        $phpLogger
            ->expects($this->once())
            ->method('log')
            ->willThrowException(new \Error('catastrophic'));

        $logger = new EmergencyLogger($phpLogger);

        try {
            $logger->log('catastrophic');

            // ✔ validation explicite
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail('EmergencyLogger ne doit JAMAIS lancer d’exception');
        }
    }
}
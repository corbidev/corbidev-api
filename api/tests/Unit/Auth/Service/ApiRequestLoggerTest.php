<?php

declare(strict_types=1);

namespace App\Tests\Auth\Service;

use App\Auth\Dto\ValidateRequest;
use App\Auth\Service\ApiRequestLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ApiRequestLoggerTest extends TestCase
{
    public function testLogWritesExpectedContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $service = new ApiRequestLogger($logger);
        $request = new ValidateRequest(
            clientId: 'client-test',
            timestamp: 1700000000,
            nonce: 'nonce-123',
            signature: 'sig-abc',
            method: 'POST',
            uri: '/api/auth/validate',
            body: '{"ok":true}',
        );

        $logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'Validation API',
                [
                    'clientId' => 'client-test',
                    'method' => 'POST',
                    'uri' => '/api/auth/validate',
                    'timestamp' => 1700000000,
                    'nonce' => 'nonce-123',
                ],
            );

        $service->log($request);
    }
}

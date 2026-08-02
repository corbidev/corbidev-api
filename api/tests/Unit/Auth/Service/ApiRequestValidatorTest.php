<?php

declare(strict_types=1);

namespace App\Tests\Auth\Service;

use App\Auth\Dto\ValidateRequest;
use App\Auth\Entity\ApiClient;
use App\Auth\Exception\Exception as AuthException;
use App\Auth\Repository\ApiClientScopeRepository;
use App\Auth\Service\ApiClientAuthenticator;
use App\Auth\Service\ApiClientScopeChecker;
use App\Auth\Service\ApiNonceValidator;
use App\Auth\Service\ApiRequestLogger;
use App\Auth\Service\ApiRequestValidator;
use App\Auth\Service\ApiSignatureValidator;
use App\Auth\Service\ApiTimestampValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Uid\Uuid;

final class ApiRequestValidatorTest extends TestCase
{
    public function testValidateExecutesAllChecksForValidRequest(): void
    {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('@1000'));

        $scopeRepository = $this->createStub(ApiClientScopeRepository::class);
        $scopeRepository
            ->method('exists')
            ->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info');

        $validator = new ApiRequestValidator(
            new ApiTimestampValidator($clock, 300),
            new ApiNonceValidator(),
            new ApiSignatureValidator(),
            new ApiClientAuthenticator(),
            new ApiClientScopeChecker($scopeRepository),
            new ApiRequestLogger($logger),
        );

        $request = new ValidateRequest(
            clientId: 'client-test',
            timestamp: 950,
            nonce: 'nonce-123',
            signature: 'expected-signature',
            method: 'POST',
            uri: '/api/auth/validate',
            body: '{"data":true}',
        );

        $client = new ApiClient('Client test', 'client-test', Uuid::v7());

        $validator->validate($request, $client, 'expected-signature', 'auth.validate');

        self::assertTrue(true);
    }

    public function testValidateStopsFlowWhenTimestampIsInvalid(): void
    {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('@1000'));

        $scopeRepository = $this->createMock(ApiClientScopeRepository::class);
        $scopeRepository
            ->expects(self::never())
            ->method('exists');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::never())
            ->method('info');

        $validator = new ApiRequestValidator(
            new ApiTimestampValidator($clock, 300),
            new ApiNonceValidator(),
            new ApiSignatureValidator(),
            new ApiClientAuthenticator(),
            new ApiClientScopeChecker($scopeRepository),
            new ApiRequestLogger($logger),
        );

        $request = new ValidateRequest(
            clientId: 'client-test',
            timestamp: 600,
            nonce: 'nonce-123',
            signature: 'expected-signature',
            method: 'POST',
            uri: '/api/auth/validate',
            body: null,
        );

        $client = new ApiClient('Client test', 'client-test', Uuid::v7());

        try {
            $validator->validate($request, $client, 'expected-signature', 'auth.validate');
            self::fail('Expected AuthException was not thrown.');
        } catch (AuthException $exception) {
            self::assertSame('AUTH_003', $exception->getErrorCode());
        }
    }
}

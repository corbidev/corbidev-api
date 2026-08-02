<?php

declare(strict_types=1);

namespace App\Tests\Auth\Service;

use App\Auth\Entity\ApiClient;
use App\Auth\Exception\Exception as AuthException;
use App\Auth\Repository\ApiClientScopeRepository;
use App\Auth\Service\ApiClientScopeChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ApiClientScopeCheckerTest extends TestCase
{
    public function testCheckAcceptsClientWithRequiredScope(): void
    {
        $repository = $this->createMock(ApiClientScopeRepository::class);
        $checker = new ApiClientScopeChecker($repository);
        $client = new ApiClient('Client test', 'client-test', Uuid::v7());

        $repository
            ->expects(self::once())
            ->method('exists')
            ->with($client, 'auth.validate')
            ->willReturn(true);

        $checker->check($client, 'auth.validate');

        self::assertTrue(true);
    }

    public function testCheckThrowsWhenScopeIsMissing(): void
    {
        $repository = $this->createMock(ApiClientScopeRepository::class);
        $checker = new ApiClientScopeChecker($repository);
        $client = new ApiClient('Client test', 'client-test', Uuid::v7());

        $repository
            ->expects(self::once())
            ->method('exists')
            ->with($client, 'auth.validate')
            ->willReturn(false);

        try {
            $checker->check($client, 'auth.validate');
            self::fail('Expected AuthException was not thrown.');
        } catch (AuthException $exception) {
            self::assertSame('AUTH_010', $exception->getErrorCode());
            self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
            self::assertSame(['auth.validate', 'client-test', 'auth.validate'], $exception->getMessageArgs());
        }
    }
}

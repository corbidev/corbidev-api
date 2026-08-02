<?php

declare(strict_types=1);

namespace App\Tests\Auth\Service;

use App\Auth\Entity\ApiClient;
use App\Auth\Exception\Exception as AuthException;
use App\Auth\Service\ApiClientAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ApiClientAuthenticatorTest extends TestCase
{
    public function testAuthenticateAcceptsEnabledClient(): void
    {
        $service = new ApiClientAuthenticator();
        $client = $this->createClient(enabled: true);

        $service->authenticate($client);

        self::assertTrue(true);
    }

    public function testAuthenticateThrowsForDisabledClient(): void
    {
        $service = new ApiClientAuthenticator();
        $client = $this->createClient(enabled: false);

        try {
            $service->authenticate($client);
            self::fail('Expected AuthException was not thrown.');
        } catch (AuthException $exception) {
            self::assertSame('AUTH_007', $exception->getErrorCode());
            self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        }
    }

    private function createClient(bool $enabled): ApiClient
    {
        $client = new ApiClient('Client test', 'client-test', Uuid::v7());

        if (!$enabled) {
            $client->disable();
        }

        return $client;
    }
}

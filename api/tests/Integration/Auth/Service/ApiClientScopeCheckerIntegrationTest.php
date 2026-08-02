<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Service;

use App\Auth\Entity\ApiClient;
use App\Auth\Entity\ApiClientScope;
use App\Auth\Exception\Exception as AuthException;
use App\Auth\Repository\ApiClientScopeRepository;
use App\Auth\Service\ApiClientScopeChecker;
use App\Tests\Integration\Auth\Repository\AuthRepositoryTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ApiClientScopeCheckerIntegrationTest extends AuthRepositoryTestCase
{
    public function testCheckAllowsClientWhenScopeExists(): void
    {
        $client = new ApiClient('Client Scope', 'client-scope', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $scope = new ApiClientScope($client, 'auth.validate', Uuid::v4());
        $this->entityManager->persist($scope);
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var ApiClient $reloadedClient */
        $reloadedClient = $this->entityManager->find(ApiClient::class, $client->getId());
        /** @var ApiClientScopeRepository $repository */
        $repository = $this->entityManager->getRepository(ApiClientScope::class);
        $service = new ApiClientScopeChecker($repository);

        $service->check($reloadedClient, 'auth.validate');

        self::assertTrue(true);
    }

    public function testCheckThrowsWhenScopeIsMissing(): void
    {
        $client = new ApiClient('Client Scope', 'client-scope-missing', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var ApiClient $reloadedClient */
        $reloadedClient = $this->entityManager->find(ApiClient::class, $client->getId());
        /** @var ApiClientScopeRepository $repository */
        $repository = $this->entityManager->getRepository(ApiClientScope::class);
        $service = new ApiClientScopeChecker($repository);

        try {
            $service->check($reloadedClient, 'auth.validate');
            self::fail('Expected AuthException was not thrown.');
        } catch (AuthException $exception) {
            self::assertSame('AUTH_010', $exception->getErrorCode());
            self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
            self::assertSame(['auth.validate', 'client-scope-missing', 'auth.validate'], $exception->getMessageArgs());
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Repository;

use App\Auth\Entity\ApiClient;
use App\Auth\Entity\ApiClientScope;
use Symfony\Component\Uid\Uuid;

final class ApiClientScopeRepositoryTest extends AuthRepositoryTestCase
{
    public function testFindByApiClientReturnsScopesSortedAlphabetically(): void
    {
        $client = new ApiClient('Client', 'client-scope', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $billingScope = new ApiClientScope($client, 'billing.write', Uuid::v4());
        $usersScope = new ApiClientScope($client, 'users.read', Uuid::v4());

        $this->entityManager->persist($billingScope);
        $this->entityManager->persist($usersScope);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloadedClient = $this->entityManager->find(ApiClient::class, $client->getId());
        $repository = $this->entityManager->getRepository(ApiClientScope::class);
        $scopes = $repository->findByApiClient($reloadedClient);

        self::assertCount(2, $scopes);
        self::assertSame('billing.write', $scopes[0]->getScope());
        self::assertSame('users.read', $scopes[1]->getScope());
    }

    public function testExistsReturnsTrueOnlyForConfiguredScope(): void
    {
        $client = new ApiClient('Client', 'client-scope-exists', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $scope = new ApiClientScope($client, 'auth.token', Uuid::v4());
        $this->entityManager->persist($scope);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloadedClient = $this->entityManager->find(ApiClient::class, $client->getId());
        $repository = $this->entityManager->getRepository(ApiClientScope::class);

        self::assertTrue($repository->exists($reloadedClient, 'auth.token'));
        self::assertFalse($repository->exists($reloadedClient, 'users.read'));
    }
}

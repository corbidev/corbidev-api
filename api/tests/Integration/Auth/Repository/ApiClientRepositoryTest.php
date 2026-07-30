<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Repository;

use App\Auth\Entity\ApiClient;
use Symfony\Component\Uid\Uuid;

final class ApiClientRepositoryTest extends AuthRepositoryTestCase
{
    public function testFindOneByClientIdReturnsMatchingClient(): void
    {
        $client = new ApiClient('Alpha Client', 'alpha-client', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $repository = $this->entityManager->getRepository(ApiClient::class);
        $found = $repository->findOneByClientId('alpha-client');

        self::assertNotNull($found);
        self::assertSame('Alpha Client', $found->getName());
        self::assertTrue($client->getId()->equals($found->getId()));
    }

    public function testFindEnabledReturnsOnlyEnabledClientsSortedByName(): void
    {
        $enabledClient = new ApiClient('Beta Client', 'beta-client', Uuid::v4());
        $disabledClient = new ApiClient('Alpha Client', 'alpha-client', Uuid::v4());
        $disabledClient->disable();

        $this->entityManager->persist($enabledClient);
        $this->entityManager->persist($disabledClient);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $repository = $this->entityManager->getRepository(ApiClient::class);
        $clients = $repository->findEnabled();

        self::assertCount(1, $clients);
        self::assertSame('Beta Client', $clients[0]->getName());
        self::assertTrue($clients[0]->isEnabled());
    }
}

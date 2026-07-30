<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Repository;

use App\Auth\Entity\ApiClient;
use App\Auth\Entity\ApiClientSecret;
use Symfony\Component\Uid\Uuid;

final class ApiClientSecretRepositoryTest extends AuthRepositoryTestCase
{
    public function testFindValidByApiClientReturnsOnlyActiveAndNonExpiredSecrets(): void
    {
        $client = new ApiClient('Client', 'client-secret', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $validSecret = new ApiClientSecret($client, 'hash-valid', Uuid::v4());
        $revokedSecret = new ApiClientSecret($client, 'hash-revoked', Uuid::v4());
        $expiredSecret = new ApiClientSecret($client, 'hash-expired', Uuid::v4());
        $expiredSecret->expireAt((new \DateTimeImmutable())->modify('-1 day'));
        $revokedSecret->revoke();

        $this->entityManager->persist($validSecret);
        $this->entityManager->persist($revokedSecret);
        $this->entityManager->persist($expiredSecret);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloadedClient = $this->entityManager->find(ApiClient::class, $client->getId());
        $repository = $this->entityManager->getRepository(ApiClientSecret::class);
        $validSecrets = $repository->findValidByApiClient($reloadedClient);

        self::assertCount(1, $validSecrets);
        self::assertSame($validSecret->getId()->toRfc4122(), $validSecrets[0]->getId()->toRfc4122());
    }

    public function testFindLatestValidByApiClientReturnsMostRecentValidSecret(): void
    {
        $client = new ApiClient('Client', 'client-latest-secret', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $oldSecret = new ApiClientSecret($client, 'hash-old', Uuid::v4());
        $newSecret = new ApiClientSecret($client, 'hash-new', Uuid::v4());
        $this->setCreatedAt($oldSecret, new \DateTimeImmutable('-2 minutes'));
        $this->setCreatedAt($newSecret, new \DateTimeImmutable('-1 minute'));

        $this->entityManager->persist($oldSecret);
        $this->entityManager->persist($newSecret);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloadedClient = $this->entityManager->find(ApiClient::class, $client->getId());
        $repository = $this->entityManager->getRepository(ApiClientSecret::class);
        $latest = $repository->findLatestValidByApiClient($reloadedClient);

        self::assertNotNull($latest);
        self::assertSame($newSecret->getId()->toRfc4122(), $latest->getId()->toRfc4122());
    }
}
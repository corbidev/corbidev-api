<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Repository;

use App\Auth\Entity\ApiClient;
use App\Auth\Entity\ApiClientSecret;
use App\Auth\Entity\ApiRequestLog;
use Symfony\Component\Uid\Uuid;

final class ApiRequestLogRepositoryTest extends AuthRepositoryTestCase
{
    public function testFindLatestByApiClientReturnsMostRecentLogs(): void
    {
        $client = new ApiClient('Client', 'client-log', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $secret = new ApiClientSecret($client, 'hash-log', Uuid::v4());
        $this->entityManager->persist($secret);
        $this->entityManager->flush();

        $oldLog = new ApiRequestLog($client, $secret, Uuid::v4(), Uuid::v4(), 'GET', '/old', '127.0.0.1', 200);
        $newLog = new ApiRequestLog($client, $secret, Uuid::v4(), Uuid::v4(), 'POST', '/new', '127.0.0.1', 201);
        $this->setCreatedAt($oldLog, new \DateTimeImmutable('-2 minutes'));
        $this->setCreatedAt($newLog, new \DateTimeImmutable('-1 minute'));

        $this->entityManager->persist($oldLog);
        $this->entityManager->persist($newLog);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $reloadedClient = $this->entityManager->find(ApiClient::class, $client->getId());
        $repository = $this->entityManager->getRepository(ApiRequestLog::class);
        $logs = $repository->findLatestByApiClient($reloadedClient, 1);

        self::assertCount(1, $logs);
        self::assertSame('/new', $logs[0]->getUri());
    }

    public function testFindOneByRequestIdReturnsMatchingLog(): void
    {
        $client = new ApiClient('Client', 'client-request-id', Uuid::v4());
        $this->entityManager->persist($client);
        $this->entityManager->flush();

        $secret = new ApiClientSecret($client, 'hash-request', Uuid::v4());
        $this->entityManager->persist($secret);
        $this->entityManager->flush();

        $requestId = Uuid::v4();
        $log = new ApiRequestLog($client, $secret, Uuid::v4(), $requestId, 'GET', '/resource', '127.0.0.1', 200);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $repository = $this->entityManager->getRepository(ApiRequestLog::class);
        $found = $repository->findOneByRequestId((string) $requestId);

        self::assertNotNull($found);
        self::assertSame($log->getId()->toRfc4122(), $found->getId()->toRfc4122());
    }
}

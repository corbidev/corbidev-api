<?php

namespace App\Tests\Logs\Repository;

use App\Logs\Entity\LogDomain;
use App\Logs\Entity\LogEnv;
use App\Logs\Entity\LogEvent;
use App\Logs\Entity\LogLevel;
use App\Logs\Entity\LogOrigin;
use App\Logs\Entity\LogUri;
use App\Logs\Repository\LogEventRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LogEventRepositoryDoctrineTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private string $fixtureToken;

    /**
     * @var array<string, LogDomain>
     */
    private array $domains = [];

    /**
     * @var array<string, LogUri>
     */
    private array $uris = [];

    /**
     * @var array<string, LogLevel>
     */
    private array $levels = [];

    /**
     * @var array<string, LogEnv>
     */
    private array $envs = [];

    protected function setUp(): void
    {
        self::bootKernel();

        $registry = static::getContainer()->get(ManagerRegistry::class);
        $entityManager = $registry->getManagerForClass(LogEvent::class);

        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $this->entityManager = $entityManager;
        $this->connection = $registry->getConnection();
        $this->connection->beginTransaction();
        $this->fixtureToken = bin2hex(random_bytes(4));
        $this->domains = [];
        $this->uris = [];
        $this->levels = [];
        $this->envs = [];
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $this->entityManager->clear();

            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testSearchAndCountSearchApplyScopeAndFiltersOnRealDatabase(): void
    {
        $scopeDomain = $this->buildDomainUrl('search-main');
        $otherDomain = $this->buildDomainUrl('search-other');

        $expected = $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('admin-logs'),
            'client' => 'front',
            'version' => '1.2.3',
            'level' => 'ERROR',
            'message' => 'expected-search-result',
            'fingerprint' => 'fp-search-expected',
            'userId' => 42,
            'httpStatus' => 500,
            'ts' => new \DateTimeImmutable('2026-04-12 10:00:00'),
        ]);

        $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('public-logs'),
            'client' => 'front',
            'version' => '1.2.3',
            'level' => 'ERROR',
            'message' => 'filtered-by-uri-like',
            'fingerprint' => 'fp-search-uri-like',
            'userId' => 42,
            'httpStatus' => 500,
            'ts' => new \DateTimeImmutable('2026-04-12 11:00:00'),
        ]);

        $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('admin-logs'),
            'client' => 'back',
            'version' => '1.2.3',
            'level' => 'ERROR',
            'message' => 'filtered-by-client',
            'fingerprint' => 'fp-search-client',
            'userId' => 42,
            'httpStatus' => 500,
            'ts' => new \DateTimeImmutable('2026-04-12 12:00:00'),
        ]);

        $this->createEvent([
            'domain' => $otherDomain,
            'uri' => $this->buildUri('admin-logs-other'),
            'client' => 'front',
            'version' => '1.2.3',
            'level' => 'ERROR',
            'message' => 'filtered-by-domain',
            'fingerprint' => 'fp-search-domain',
            'userId' => 42,
            'httpStatus' => 500,
            'ts' => new \DateTimeImmutable('2026-04-12 13:00:00'),
        ]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $repository = $this->getRepository();
        $from = new \DateTimeImmutable('2026-04-10 00:00:00');
        $to = new \DateTimeImmutable('2026-04-15 23:59:59');

        $result = $repository->search(
            ['domain' => $scopeDomain, 'client' => 'front', 'version' => '1.2.3'],
            'error',
            42,
            500,
            [$this->buildUri('admin-logs')],
            '/admin',
            $from,
            $to,
            1,
            10
        );

        $count = $repository->countSearch(
            ['domain' => $scopeDomain, 'client' => 'front', 'version' => '1.2.3'],
            'error',
            42,
            500,
            [$this->buildUri('admin-logs')],
            '/admin',
            $from,
            $to
        );

        self::assertCount(1, $result);
        self::assertSame($expected->getMessage(), $result[0]->getMessage());
        self::assertSame(42, $result[0]->getUserId());
        self::assertSame(500, $result[0]->getHttpStatus());
        self::assertSame(1, $count);
    }

    public function testSearchWithContextReturnsOnlyMatchingGroups(): void
    {
        $scopeDomain = $this->buildDomainUrl('context');

        $first = $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('health'),
            'level' => 'ERROR',
            'message' => 'context-health-error',
            'fingerprint' => 'fp-context-1',
            'ts' => new \DateTimeImmutable('2026-04-14 09:00:00'),
        ]);

        $second = $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('jobs'),
            'level' => 'WARNING',
            'message' => 'context-jobs-warning',
            'fingerprint' => 'fp-context-2',
            'ts' => new \DateTimeImmutable('2026-04-14 08:00:00'),
        ]);

        $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('health'),
            'level' => 'INFO',
            'message' => 'context-health-info',
            'fingerprint' => 'fp-context-3',
            'ts' => new \DateTimeImmutable('2026-04-14 07:00:00'),
        ]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $result = $this->getRepository()->searchWithContext(
            ['domain' => $scopeDomain],
            [
                ['uri' => $this->buildUri('health'), 'level' => 'error'],
                ['uri' => $this->buildUri('jobs'), 'level' => 'warning'],
            ],
            10
        );

        self::assertCount(2, $result);
        self::assertSame(
            [$first->getMessage(), $second->getMessage()],
            array_map(static fn (LogEvent $event): string => $event->getMessage(), $result)
        );
    }

    public function testFindTopErrorsAndCountByDayAggregateScopedEvents(): void
    {
        $scopeDomain = $this->buildDomainUrl('stats');
        $otherDomain = $this->buildDomainUrl('stats-other');

        $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('stats-a1'),
            'level' => 'ERROR',
            'message' => 'stats-a1',
            'fingerprint' => 'fp-top-a',
            'ts' => new \DateTimeImmutable('2026-04-14 10:00:00'),
        ]);

        $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('stats-a2'),
            'level' => 'ERROR',
            'message' => 'stats-a2',
            'fingerprint' => 'fp-top-a',
            'ts' => new \DateTimeImmutable('2026-04-14 11:00:00'),
        ]);

        $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('stats-b1'),
            'level' => 'WARNING',
            'message' => 'stats-b1',
            'fingerprint' => 'fp-top-b',
            'ts' => new \DateTimeImmutable('2026-04-13 09:00:00'),
        ]);

        $this->createEvent([
            'domain' => $otherDomain,
            'uri' => $this->buildUri('stats-outside'),
            'level' => 'ERROR',
            'message' => 'stats-outside',
            'fingerprint' => 'fp-top-a',
            'ts' => new \DateTimeImmutable('2026-04-14 12:00:00'),
        ]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $repository = $this->getRepository();
        $topErrors = $repository->findTopErrors(['domain' => $scopeDomain], 10);
        $byDay = $repository->countByDay(['domain' => $scopeDomain], 30);

        self::assertNotEmpty($topErrors);
        self::assertSame('fp-top-a', $topErrors[0]['fingerprint']);
        self::assertSame('2', (string) $topErrors[0]['total']);

        $indexedByDay = [];

        foreach ($byDay as $row) {
            $indexedByDay[(string) $row['day']] = (string) $row['total'];
        }

        self::assertSame('2', $indexedByDay['2026-04-14'] ?? null);
        self::assertSame('1', $indexedByDay['2026-04-13'] ?? null);
    }

    public function testFindByUserReturnsOnlyScopedUserEventsSortedByDateDesc(): void
    {
        $scopeDomain = $this->buildDomainUrl('user');
        $otherDomain = $this->buildDomainUrl('user-other');

        $latest = $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('user-latest'),
            'message' => 'user-latest',
            'fingerprint' => 'fp-user-latest',
            'userId' => 99,
            'ts' => new \DateTimeImmutable('2026-04-15 11:00:00'),
        ]);

        $oldest = $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('user-oldest'),
            'message' => 'user-oldest',
            'fingerprint' => 'fp-user-oldest',
            'userId' => 99,
            'ts' => new \DateTimeImmutable('2026-04-15 09:00:00'),
        ]);

        $this->createEvent([
            'domain' => $scopeDomain,
            'uri' => $this->buildUri('user-other-id'),
            'message' => 'user-other-id',
            'fingerprint' => 'fp-user-other-id',
            'userId' => 100,
            'ts' => new \DateTimeImmutable('2026-04-15 10:00:00'),
        ]);

        $this->createEvent([
            'domain' => $otherDomain,
            'uri' => $this->buildUri('user-other-domain'),
            'message' => 'user-other-domain',
            'fingerprint' => 'fp-user-other-domain',
            'userId' => 99,
            'ts' => new \DateTimeImmutable('2026-04-15 12:00:00'),
        ]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $result = $this->getRepository()->findByUser(['domain' => $scopeDomain], 99, 10);

        self::assertCount(2, $result);
        self::assertSame(
            [$latest->getMessage(), $oldest->getMessage()],
            array_map(static fn (LogEvent $event): string => $event->getMessage(), $result)
        );
    }

    private function getRepository(): LogEventRepository
    {
        $repository = $this->entityManager->getRepository(LogEvent::class);

        self::assertInstanceOf(LogEventRepository::class, $repository);

        return $repository;
    }

    /**
     * @param array{
     *     domain: string,
     *     uri: string,
     *     client?: string,
     *     version?: string,
     *     method?: string,
     *     level?: string,
     *     message?: string,
     *     fingerprint?: string,
     *     userId?: int|null,
     *     httpStatus?: int|null,
     *     ts?: \DateTimeImmutable
     * } $options
     */
    private function createEvent(array $options): LogEvent
    {
        $domain = $this->getOrCreateDomain($options['domain']);
        $uri = $this->getOrCreateUri($options['uri']);
        $levelName = strtoupper($options['level'] ?? 'INFO');

        $origin = new LogOrigin();
        $origin
            ->setDomain($domain)
            ->setUri($uri)
            ->setMethod($options['method'] ?? 'GET')
            ->setClient($options['client'] ?? 'front')
            ->setVersion($options['version'] ?? '1.0.0');

        $event = new LogEvent();
        $event
            ->setTs($options['ts'] ?? new \DateTimeImmutable())
            ->setLevel($this->getOrCreateLevel($levelName))
            ->setEnv($this->getOrCreateEnv('test'))
            ->setOrigin($origin)
            ->setMessage($options['message'] ?? ('message-' . $this->fixtureToken))
            ->setFingerprint($options['fingerprint'] ?? ('fingerprint-' . $this->fixtureToken))
            ->setUserId($options['userId'] ?? null)
            ->setHttpStatus($options['httpStatus'] ?? null);

        $this->entityManager->persist($origin);
        $this->entityManager->persist($event);

        return $event;
    }

    private function getOrCreateDomain(string $url): LogDomain
    {
        if (isset($this->domains[$url])) {
            return $this->domains[$url];
        }

        $domain = new LogDomain();
        $domain
            ->setUrl($url)
            ->setIsActive(true);

        $this->entityManager->persist($domain);
        $this->domains[$url] = $domain;

        return $domain;
    }

    private function getOrCreateUri(string $uriValue): LogUri
    {
        if (isset($this->uris[$uriValue])) {
            return $this->uris[$uriValue];
        }

        $uri = new LogUri();
        $uri->setUri($uriValue);

        $this->entityManager->persist($uri);
        $this->uris[$uriValue] = $uri;

        return $uri;
    }

    private function getOrCreateLevel(string $name): LogLevel
    {
        if (isset($this->levels[$name])) {
            return $this->levels[$name];
        }

        $level = $this->entityManager->getRepository(LogLevel::class)->findOneBy(['name' => $name]);

        if (!$level instanceof LogLevel) {
            $level = new LogLevel();
            $level
                ->setName($name)
                ->setLevelInt($this->resolveLevelInt($name));

            $this->entityManager->persist($level);
        }

        $this->levels[$name] = $level;

        return $level;
    }

    private function getOrCreateEnv(string $name): LogEnv
    {
        if (isset($this->envs[$name])) {
            return $this->envs[$name];
        }

        $env = $this->entityManager->getRepository(LogEnv::class)->findOneBy(['name' => $name]);

        if (!$env instanceof LogEnv) {
            $env = new LogEnv();
            $env->setName($name);
            $this->entityManager->persist($env);
        }

        $this->envs[$name] = $env;

        return $env;
    }

    private function resolveLevelInt(string $name): int
    {
        return match ($name) {
            'EMERGENCY' => 600,
            'ALERT' => 550,
            'CRITICAL' => 500,
            'ERROR' => 400,
            'WARNING' => 300,
            default => 200,
        };
    }

    private function buildDomainUrl(string $label): string
    {
        return sprintf('https://%s-%s.example.test', $label, $this->fixtureToken);
    }

    private function buildUri(string $label): string
    {
        return sprintf('/%s/%s', $this->fixtureToken, $label);
    }
}
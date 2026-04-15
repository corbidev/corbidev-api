<?php

namespace App\Tests\Logs\Repository;

use App\Logs\Repository\LogEventRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LogEventRepositoryTest extends TestCase
{
    public function testSearchRequiresDomainInScope(): void
    {
        [$qb] = $this->createQueryBuilderSpy();
        $repository = $this->createRepository($qb);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain is required for scoped queries');

        $repository->search([]);
    }

    public function testSearchBuildsScopedPaginatedQuery(): void
    {
        $expectedResult = [$this->createStub(\App\Logs\Entity\LogEvent::class)];
        [$qb, $state] = $this->createQueryBuilderSpy($expectedResult);
        $repository = $this->createRepository($qb);

        $from = new \DateTimeImmutable('2026-04-01 00:00:00');
        $to = new \DateTimeImmutable('2026-04-15 23:59:59');

        $result = $repository->search(
            ['domain' => 'https://api.corbisier.fr', 'client' => 'front', 'version' => '1.2.3'],
            'error',
            42,
            500,
            ['/logs', '/admin/logs'],
            '/admin',
            $from,
            $to,
            2,
            25
        );

        self::assertSame($expectedResult, $result);
        self::assertContains('d.url = :scope_domain', $state->andWhere);
        self::assertContains('o.client = :scope_client', $state->andWhere);
        self::assertContains('o.version = :scope_version', $state->andWhere);
        self::assertContains('lvl.name = :level', $state->andWhere);
        self::assertContains('l.userId = :userId', $state->andWhere);
        self::assertContains('l.httpStatus = :status', $state->andWhere);
        self::assertContains('u.uri IN (:uris)', $state->andWhere);
        self::assertContains('u.uri LIKE :uriLike', $state->andWhere);
        self::assertContains('l.ts >= :from', $state->andWhere);
        self::assertContains('l.ts <= :to', $state->andWhere);
        self::assertSame('https://api.corbisier.fr', $state->parameters['scope_domain']);
        self::assertSame('front', $state->parameters['scope_client']);
        self::assertSame('1.2.3', $state->parameters['scope_version']);
        self::assertSame('ERROR', $state->parameters['level']);
        self::assertSame(42, $state->parameters['userId']);
        self::assertSame(500, $state->parameters['status']);
        self::assertSame(['/logs', '/admin/logs'], $state->parameters['uris']);
        self::assertSame('%/admin%', $state->parameters['uriLike']);
        self::assertSame($from, $state->parameters['from']);
        self::assertSame($to, $state->parameters['to']);
        self::assertSame(['l.ts', 'DESC'], $state->orderBy);
        self::assertSame(25, $state->firstResult);
        self::assertSame(25, $state->maxResults);
    }

    public function testCountSearchReturnsIntegerCount(): void
    {
        [$qb, $state] = $this->createQueryBuilderSpy([], '7');
        $repository = $this->createRepository($qb);

        $result = $repository->countSearch(['domain' => 'https://api.corbisier.fr'], 'warning');

        self::assertSame(7, $result);
        self::assertSame(['COUNT(l.id)'], $state->select);
        self::assertSame('WARNING', $state->parameters['level']);
    }

    public function testSearchWithContextBuildsGroupedOrExpression(): void
    {
        $expectedResult = [$this->createStub(\App\Logs\Entity\LogEvent::class)];
        [$qb, $state] = $this->createQueryBuilderSpy($expectedResult);
        $repository = $this->createRepository($qb);

        $result = $repository->searchWithContext(
            ['domain' => 'https://api.corbisier.fr'],
            [
                ['uri' => '/health', 'level' => 'error'],
                ['level' => 'warning'],
                [],
            ],
            10
        );

        self::assertSame($expectedResult, $result);
        self::assertSame('/health', $state->parameters['uri_0']);
        self::assertSame('ERROR', $state->parameters['level_0']);
        self::assertSame('WARNING', $state->parameters['level_1']);
        self::assertSame(10, $state->maxResults);
        self::assertTrue($this->containsOrExpression($state->andWhere));
    }

    /**
     * @return array{0: QueryBuilder, 1: object{andWhere: list<mixed>, parameters: array<string, mixed>, orderBy: list<mixed>|null, firstResult: int|null, maxResults: int|null, select: list<mixed>}}
     */
    private function createQueryBuilderSpy(array $result = [], ?string $singleScalarResult = null): array
    {
        $state = (object) [
            'andWhere' => [],
            'parameters' => [],
            'orderBy' => null,
            'firstResult' => null,
            'maxResults' => null,
            'select' => [],
        ];

        $query = $this->createStub(Query::class);
        $query->method('getResult')->willReturn($result);
        $query->method('getArrayResult')->willReturn($result);

        if ($singleScalarResult !== null) {
            $query->method('getSingleScalarResult')->willReturn($singleScalarResult);
        }

        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('leftJoin')->willReturn($qb);
        $qb->method('addSelect')->willReturn($qb);
        $qb->method('select')->willReturnCallback(function (...$args) use ($qb, $state) {
            $state->select = $args;

            return $qb;
        });
        $qb->method('andWhere')->willReturnCallback(function (...$args) use ($qb, $state) {
            $state->andWhere[] = $args[0];

            return $qb;
        });
        $qb->method('setParameter')->willReturnCallback(function (string $name, mixed $value) use ($qb, $state) {
            $state->parameters[$name] = $value;

            return $qb;
        });
        $qb->method('orderBy')->willReturnCallback(function (...$args) use ($qb, $state) {
            $state->orderBy = $args;

            return $qb;
        });
        $qb->method('setFirstResult')->willReturnCallback(function (int $firstResult) use ($qb, $state) {
            $state->firstResult = $firstResult;

            return $qb;
        });
        $qb->method('setMaxResults')->willReturnCallback(function (int $maxResults) use ($qb, $state) {
            $state->maxResults = $maxResults;

            return $qb;
        });
        $qb->method('groupBy')->willReturn($qb);
        $qb->method('expr')->willReturn(new Expr());
        $qb->method('getQuery')->willReturn($query);

        return [$qb, $state];
    }

    private function createRepository(QueryBuilder $qb): LogEventRepository
    {
        $repository = $this->getMockBuilder(LogEventRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects(self::once())->method('createQueryBuilder')->willReturnCallback(function (string $alias) use ($qb) {
            self::assertSame('l', $alias);

            return $qb;
        });

        return $repository;
    }

    /**
     * @param list<mixed> $clauses
     */
    private function containsOrExpression(array $clauses): bool
    {
        foreach ($clauses as $clause) {
            if ($clause instanceof Orx) {
                return true;
            }
        }

        return false;
    }
}
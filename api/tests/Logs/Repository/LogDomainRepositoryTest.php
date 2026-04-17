<?php

namespace App\Tests\Logs\Repository;

use App\Api\Logs\Entity\LogDomain;
use App\Api\Logs\Repository\LogDomainRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class LogDomainRepositoryTest extends TestCase
{
    public function testFindActiveDomainsFiltersOnActiveFlag(): void
    {
        $domain = (new LogDomain())
            ->setUrl('https://api.corbisier.fr/')
            ->setIsActive(true);

        [$qb, $state] = $this->createQueryBuilderSpy([$domain]);
        $repository = $this->createRepository($qb);

        $result = $repository->findActiveDomains();

        self::assertSame([$domain], $result);
        self::assertSame(['d.isActive = 1'], $state->andWhere);
        self::assertSame('https://api.corbisier.fr', $result[0]->getUrl());
        self::assertTrue($result[0]->isActive());
    }

    /**
     * @return array{0: QueryBuilder, 1: object{andWhere: list<mixed>}}
     */
    private function createQueryBuilderSpy(array $result): array
    {
        $state = (object) [
            'andWhere' => [],
        ];

        $query = $this->createStub(Query::class);
        $query->method('getResult')->willReturn($result);

        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('andWhere')->willReturnCallback(function (...$args) use ($qb, $state) {
            $state->andWhere[] = $args[0];

            return $qb;
        });
        $qb->method('getQuery')->willReturn($query);

        return [$qb, $state];
    }

    private function createRepository(QueryBuilder $qb): LogDomainRepository
    {
        $repository = $this->getMockBuilder(LogDomainRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects(self::once())->method('createQueryBuilder')->willReturnCallback(function (string $alias) use ($qb) {
            self::assertSame('d', $alias);

            return $qb;
        });

        return $repository;
    }
}
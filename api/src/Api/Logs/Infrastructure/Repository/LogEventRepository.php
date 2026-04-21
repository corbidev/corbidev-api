<?php

namespace App\Api\Logs\Infrastructure\Repository;

use App\Api\Logs\Domain\Entity\LogEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogEvent::class);
    }

    public function search(
        ?string $domain = null,
        ?string $level = null,
        ?int $userId = null,
        ?int $httpStatus = null,
        ?string $uriLike = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        int $limit = 50
    ): array {
        $qb = $this->createQueryBuilder('l');

        if ($domain) {
            $qb->andWhere('l.domain = :domain')
               ->setParameter('domain', $domain);
        }

        if ($level) {
            $qb->andWhere('l.levelName = :level')
               ->setParameter('level', strtoupper($level));
        }

        if ($userId !== null) {
            $qb->andWhere('l.userId = :userId')
               ->setParameter('userId', $userId);
        }

        if ($httpStatus !== null) {
            $qb->andWhere('l.httpStatus = :status')
               ->setParameter('status', $httpStatus);
        }

        if ($uriLike) {
            $qb->andWhere('l.uri LIKE :uri')
               ->setParameter('uri', '%' . $uriLike . '%');
        }

        if ($from) {
            $qb->andWhere('l.ts >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('l.ts <= :to')
               ->setParameter('to', $to);
        }

        return $qb
            ->orderBy('l.ts', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopErrors(?string $domain = null, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('l.fingerprint AS fingerprint, COUNT(l.id) AS total')
            ->groupBy('l.fingerprint')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit);

        if ($domain) {
            $qb->andWhere('l.domain = :domain')
               ->setParameter('domain', $domain);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function findByUser(string $domain, int $userId, int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.domain = :domain')
            ->andWhere('l.userId = :userId')
            ->setParameter('domain', $domain)
            ->setParameter('userId', $userId)
            ->orderBy('l.ts', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
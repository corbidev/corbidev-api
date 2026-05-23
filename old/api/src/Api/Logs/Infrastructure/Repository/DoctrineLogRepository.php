<?php

declare(strict_types=1);

namespace App\Api\Logs\Infrastructure\Repository;

use App\Api\Logs\Domain\Repository\LogRepositoryInterface;
use App\Api\Logs\Domain\Entity\LogEvent;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineLogRepository implements LogRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    public function existsByExternalId(string $externalId): bool
    {
        return (bool) $this->em->getRepository(LogEvent::class)
            ->createQueryBuilder('l')
            ->select('1')
            ->where('l.externalId = :externalId')
            ->setParameter('externalId', $externalId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
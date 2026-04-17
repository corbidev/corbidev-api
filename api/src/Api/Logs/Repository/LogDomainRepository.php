<?php

namespace App\Api\Logs\Repository;

use App\Api\Logs\Entity\LogDomain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogDomain>
 */
class LogDomainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogDomain::class);
    }

    /**
     * Retourne uniquement les domaines actifs.
     *
     * @return list<LogDomain>
     */
    public function findActiveDomains(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.isActive = 1')
            ->getQuery()
            ->getResult();
    }
}
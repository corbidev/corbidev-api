<?php

namespace App\RessLogs\Repository;

use App\RessLogs\Entity\LogUri;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogUri>
 */
class LogUriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogUri::class);
    }

    public function deleteOrphans(): void
    {
        $this->getEntityManager()->createQuery(
            'DELETE FROM App\\RessLogs\\Entity\\LogUri u
             WHERE NOT EXISTS (
                SELECT 1 FROM App\\RessLogs\\Entity\\LogEntry e WHERE e.uri = u
             )'
        )->execute();
    }
}
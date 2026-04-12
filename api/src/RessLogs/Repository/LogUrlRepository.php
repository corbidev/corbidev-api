<?php

namespace App\RessLogs\Repository;

use App\RessLogs\Entity\LogUrl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogUrl>
 */
class LogUrlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogUrl::class);
    }

    public function deleteOrphans(): void
    {
        $this->getEntityManager()->createQuery(
            'DELETE FROM App\\RessLogs\\Entity\\LogUrl u
             WHERE NOT EXISTS (
                SELECT 1 FROM App\\RessLogs\\Entity\\LogEntry e WHERE e.url = u
             )
             AND NOT EXISTS (
                SELECT 1 FROM App\\RessLogs\\Entity\\LogUri i WHERE i.url = u
             )'
        )->execute();
    }
}

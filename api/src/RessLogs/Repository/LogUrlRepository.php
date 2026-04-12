<?php

namespace App\RessLogs\Repository;

use App\RessLogs\Entity\LogUrl;
use App\RessLogs\RessLogsConstants;
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
        $this->getEntityManager()->createQuery(RessLogsConstants::DQL_DELETE_ORPHAN_URLS)->execute();
    }
}

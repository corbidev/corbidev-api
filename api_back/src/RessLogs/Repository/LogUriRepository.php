<?php

namespace App\RessLogs\Repository;

use App\RessLogs\Entity\LogUri;
use App\RessLogs\RessLogsConstants;
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
        $this->getEntityManager()->createQuery(RessLogsConstants::DQL_DELETE_ORPHAN_URIS)->execute();
    }
}
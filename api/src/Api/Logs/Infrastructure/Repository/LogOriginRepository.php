<?php

namespace App\Api\Logs\Infrastructure\Repository;

use App\Api\Logs\Domain\Entity\LogOrigin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogOriginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogOrigin::class);
    }

    public function findOneByScope(string $domain, string $client, string $version): ?LogOrigin
    {
        return $this->findOneBy([
            'domain' => $domain,
            'client' => $client,
            'version' => $version,
        ]);
    }
}
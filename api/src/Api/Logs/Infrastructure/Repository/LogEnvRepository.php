<?php

namespace App\Api\Logs\Infrastructure\Repository;

use App\Api\Logs\Domain\Entity\LogEnv;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogEnvRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogEnv::class);
    }

    public function findOneByName(string $name): ?LogEnv
    {
        return $this->findOneBy(['name' => strtolower($name)]);
    }
}
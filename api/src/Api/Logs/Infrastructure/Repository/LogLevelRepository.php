<?php

namespace App\Api\Logs\Infrastructure\Repository;

use App\Api\Logs\Domain\Entity\LogLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogLevel::class);
    }

    public function findOneByName(string $name): ?LogLevel
    {
        return $this->findOneBy(['name' => strtoupper($name)]);
    }
}
<?php

namespace App\Api\Logs\Infrastructure\Repository;

use App\Api\Logs\Domain\Entity\LogErrorCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogErrorCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogErrorCode::class);
    }

    public function findOneByCode(string $code): ?LogErrorCode
    {
        return $this->findOneBy(['code' => strtoupper($code)]);
    }
}
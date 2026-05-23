<?php

namespace App\Api\Jwt\Repository;

use App\Api\Jwt\Entity\ApiConsumer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApiConsumerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiConsumer::class);
    }

    public function findOneByIdentifier(string $identifier): ?ApiConsumer
    {
        return $this->findOneBy(['identifier' => $identifier]);
    }
}

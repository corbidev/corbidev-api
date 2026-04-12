<?php

namespace App\RessAuth\Repository;

use App\RessAuth\Entity\AuthCredential;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthCredential>
 */
class AuthCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthCredential::class);
    }

    public function findActiveOneBySourceApiKey(string $sourceApiKey): ?AuthCredential
    {
        return $this->createQueryBuilder('credential')
            ->andWhere('credential.apiKey = :sourceApiKey')
            ->andWhere('credential.isActive = true')
            ->setParameter('sourceApiKey', $sourceApiKey)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
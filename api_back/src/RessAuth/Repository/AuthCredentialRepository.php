<?php

namespace App\RessAuth\Repository;

use App\RessAuth\Entity\AuthCredential;
use App\RessAuth\RessAuthConstants;
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
        return $this->createQueryBuilder(RessAuthConstants::QUERY_ALIAS_CREDENTIAL)
            ->andWhere(RessAuthConstants::QUERY_CREDENTIAL_API_KEY)
            ->andWhere(RessAuthConstants::QUERY_CREDENTIAL_IS_ACTIVE)
            ->setParameter(RessAuthConstants::QUERY_PARAM_SOURCE_API_KEY, $sourceApiKey)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
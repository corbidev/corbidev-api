<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use App\Auth\Entity\ApiClient;
use App\Auth\Entity\ApiClientSecret;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository permettant d'accéder aux secrets des clients API.
 */
class ApiClientSecretRepository extends ServiceEntityRepository
{
    /**
     * Initialise le repository.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiClientSecret::class);
    }

    /**
     * Retourne les secrets d'un client.
     *
     * @return ApiClientSecret[]
     */
    public function findByApiClient(ApiClient $apiClient): array
    {
        return $this->createQueryBuilder('secret')
            ->andWhere('secret.apiClient = :apiClient')
            ->setParameter('apiClient', $apiClient)
            ->orderBy('secret.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les secrets valides d'un client.
     *
     * @return ApiClientSecret[]
     */
    public function findValidByApiClient(ApiClient $apiClient): array
    {
        return $this->createQueryBuilder('secret')
            ->andWhere('secret.apiClient = :apiClient')
            ->andWhere('secret.revokedAt IS NULL')
            ->andWhere('(secret.expiresAt IS NULL OR secret.expiresAt > :now)')
            ->setParameter('apiClient', $apiClient)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('secret.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le secret valide le plus récent d'un client.
     */
    public function findLatestValidByApiClient(ApiClient $apiClient): ?ApiClientSecret
    {
        return $this->createQueryBuilder('secret')
            ->andWhere('secret.apiClient = :apiClient')
            ->andWhere('secret.revokedAt IS NULL')
            ->andWhere('(secret.expiresAt IS NULL OR secret.expiresAt > :now)')
            ->setParameter('apiClient', $apiClient)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('secret.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Enregistre un secret.
     */
    public function save(ApiClientSecret $apiClientSecret, bool $flush = false): void
    {
        $this->getEntityManager()->persist($apiClientSecret);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un secret.
     */
    public function remove(ApiClientSecret $apiClientSecret, bool $flush = false): void
    {
        $this->getEntityManager()->remove($apiClientSecret);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use App\Auth\Entity\ApiClient;
use App\Auth\Entity\ApiRequestLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * Repository permettant d'accéder aux journaux de requêtes API.
 */
class ApiRequestLogRepository extends ServiceEntityRepository
{
    /**
     * Initialise le repository.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiRequestLog::class);
    }

    /**
     * Retourne les dernières requêtes d'un client.
     *
     * @return ApiRequestLog[]
     */
    public function findLatestByApiClient(
        ApiClient $apiClient,
        int $limit = 100,
    ): array {
        return $this->createQueryBuilder('requestLog')
            ->andWhere('IDENTITY(requestLog.apiClient) = :apiClientId')
            ->setParameter('apiClientId', $apiClient->getId(), UuidType::NAME)
            ->orderBy('requestLog.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche une requête par son requestId.
     */
    public function findOneByRequestId(string $requestId): ?ApiRequestLog
    {
        return $this->findOneBy([
            'requestId' => $requestId,
        ]);
    }

    /**
     * Enregistre un journal de requête.
     */
    public function save(ApiRequestLog $apiRequestLog, bool $flush = false): void
    {
        $this->getEntityManager()->persist($apiRequestLog);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un journal de requête.
     */
    public function remove(ApiRequestLog $apiRequestLog, bool $flush = false): void
    {
        $this->getEntityManager()->remove($apiRequestLog);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
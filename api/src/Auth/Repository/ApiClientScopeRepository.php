<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use App\Auth\Entity\ApiClient;
use App\Auth\Entity\ApiClientScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository permettant d'accéder aux scopes des clients API.
 */
class ApiClientScopeRepository extends ServiceEntityRepository
{
    /**
     * Initialise le repository.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiClientScope::class);
    }

    /**
     * Retourne tous les scopes d'un client.
     *
     * @return ApiClientScope[]
     */
    public function findByApiClient(ApiClient $apiClient): array
    {
        return $this->createQueryBuilder('scope')
            ->andWhere('scope.apiClient = :apiClient')
            ->setParameter('apiClient', $apiClient)
            ->orderBy('scope.scope', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche un scope précis pour un client.
     */
    public function findOneByApiClientAndScope(
        ApiClient $apiClient,
        string $scope,
    ): ?ApiClientScope {
        return $this->findOneBy([
            'apiClient' => $apiClient,
            'scope' => $scope,
        ]);
    }

    /**
     * Vérifie qu'un client possède un scope.
     */
    public function exists(
        ApiClient $apiClient,
        string $scope,
    ): bool {
        return $this->createQueryBuilder('scope')
            ->select('COUNT(scope.id)')
            ->andWhere('scope.apiClient = :apiClient')
            ->andWhere('scope.scope = :scope')
            ->setParameter('apiClient', $apiClient)
            ->setParameter('scope', $scope)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Enregistre un scope.
     */
    public function save(ApiClientScope $apiClientScope, bool $flush = false): void
    {
        $this->getEntityManager()->persist($apiClientScope);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un scope.
     */
    public function remove(ApiClientScope $apiClientScope, bool $flush = false): void
    {
        $this->getEntityManager()->remove($apiClientScope);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
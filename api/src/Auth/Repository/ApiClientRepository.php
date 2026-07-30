<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use App\Auth\Entity\ApiClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository permettant d'accéder aux clients API.
 */
class ApiClientRepository extends ServiceEntityRepository
{
    /**
     * Initialise le repository.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiClient::class);
    }

    /**
     * Recherche un client par son clientId.
     */
    public function findOneByClientId(string $clientId): ?ApiClient
    {
        return $this->findOneBy([
            'clientId' => $clientId,
        ]);
    }

    /**
     * Retourne tous les clients actifs.
     *
     * @return ApiClient[]
     */
    public function findEnabled(): array
    {
        return $this->createQueryBuilder('client')
            ->andWhere('client.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('client.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Enregistre un client.
     */
    public function save(ApiClient $apiClient, bool $flush = false): void
    {
        $this->getEntityManager()->persist($apiClient);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un client.
     */
    public function remove(ApiClient $apiClient, bool $flush = false): void
    {
        $this->getEntityManager()->remove($apiClient);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
<?php

namespace App\Logs\Repository;

use App\Logs\Entity\LogEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

/**
 * @extends ServiceEntityRepository<LogEvent>
 */
class LogEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogEvent::class);
    }

    /**
     * Construit la requete de base avec les jointures necessaires a la lecture.
     */
    private function baseQb(): QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.level', 'lvl')->addSelect('lvl')
            ->leftJoin('l.origin', 'o')->addSelect('o')
            ->leftJoin('o.domain', 'd')->addSelect('d')
            ->leftJoin('o.uri', 'u')->addSelect('u');
    }

    /**
     * Applique le scope multi-tenant obligatoire sur une requete.
     *
     * @param array{domain?: string, client?: string|null, version?: string|null} $scope
     *
     * @throws InvalidArgumentException Si le domaine n'est pas fourni.
     */
    private function applyScope(QueryBuilder $qb, array $scope): void
    {
        if (empty($scope['domain'])) {
            throw new InvalidArgumentException('Domain is required for scoped queries');
        }

        $qb->andWhere('d.url = :scope_domain')
           ->setParameter('scope_domain', $scope['domain']);

        if (!empty($scope['client'])) {
            $qb->andWhere('o.client = :scope_client')
               ->setParameter('scope_client', $scope['client']);
        }

        if (!empty($scope['version'])) {
            $qb->andWhere('o.version = :scope_version')
               ->setParameter('scope_version', $scope['version']);
        }
    }

    /**
     * Ajoute les filtres metier complementaires a une requete deja scopee.
     *
     * @param list<string>|null $uris
     */
    private function applyFilters(
        QueryBuilder $qb,
        ?string $level,
        ?int $userId,
        ?int $httpStatus,
        ?array $uris,
        ?string $uriLike,
        ?\DateTimeInterface $from,
        ?\DateTimeInterface $to
    ): void {
        if ($level) {
            $qb->andWhere('lvl.name = :level')
               ->setParameter('level', strtoupper($level));
        }

        if ($userId !== null) {
            $qb->andWhere('l.userId = :userId')
               ->setParameter('userId', $userId);
        }

        if ($httpStatus !== null) {
            $qb->andWhere('l.httpStatus = :status')
               ->setParameter('status', $httpStatus);
        }

        if ($uris) {
            $qb->andWhere('u.uri IN (:uris)')
               ->setParameter('uris', $uris);
        }

        if ($uriLike) {
            $qb->andWhere('u.uri LIKE :uriLike')
               ->setParameter('uriLike', '%' . $uriLike . '%');
        }

        if ($from) {
            $qb->andWhere('l.ts >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('l.ts <= :to')
               ->setParameter('to', $to);
        }
    }

    /**
     * Recherche paginee des evenements de log dans un scope tenant donne.
     *
     * @param array{domain?: string, client?: string|null, version?: string|null} $scope
     * @param list<string>|null $uris
     * @return list<LogEvent>
     */
    public function search(
        array $scope,
        ?string $level = null,
        ?int $userId = null,
        ?int $httpStatus = null,
        ?array $uris = null,
        ?string $uriLike = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        int $page = 1,
        int $limit = 50
    ): array {
        $qb = $this->baseQb();

        $this->applyScope($qb, $scope);
        $this->applyFilters($qb, $level, $userId, $httpStatus, $uris, $uriLike, $from, $to);

        return $qb
            ->orderBy('l.ts', 'DESC')
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de resultats correspondant a une recherche scopee.
     *
     * @param array{domain?: string, client?: string|null, version?: string|null} $scope
     * @param list<string>|null $uris
     */
    public function countSearch(
        array $scope,
        ?string $level = null,
        ?int $userId = null,
        ?int $httpStatus = null,
        ?array $uris = null,
        ?string $uriLike = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null
    ): int {
        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->leftJoin('l.level', 'lvl')
            ->leftJoin('l.origin', 'o')
            ->leftJoin('o.domain', 'd')
            ->leftJoin('o.uri', 'u');

        $this->applyScope($qb, $scope);
        $this->applyFilters($qb, $level, $userId, $httpStatus, $uris, $uriLike, $from, $to);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Recherche des evenements a partir de groupes de criteres combines en OR.
     *
     * @param array{domain?: string, client?: string|null, version?: string|null} $scope
     * @param list<array{uri?: string, level?: string}> $groups
     * @return list<LogEvent>
     */
    public function searchWithContext(array $scope, array $groups, int $limit = 100): array
    {
        $qb = $this->baseQb();
        $expr = $qb->expr();

        $this->applyScope($qb, $scope);

        $orX = $expr->orX();

        foreach ($groups as $i => $group) {
            $andX = $expr->andX();

            if (!empty($group['uri'])) {
                $andX->add("u.uri = :uri_$i");
                $qb->setParameter("uri_$i", $group['uri']);
            }

            if (!empty($group['level'])) {
                $andX->add("lvl.name = :level_$i");
                $qb->setParameter("level_$i", strtoupper($group['level']));
            }

            if ($andX->count() > 0) {
                $orX->add($andX);
            }
        }

        if ($orX->count() > 0) {
            $qb->andWhere($orX);
        }

        return $qb
            ->orderBy('l.ts', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les empreintes d'erreur les plus frequentes dans un scope donne.
     *
     * @param array{domain?: string, client?: string|null, version?: string|null} $scope
     * @return list<array{fingerprint: mixed, total: mixed}>
     */
    public function findTopErrors(array $scope, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('l.fingerprint AS fingerprint, COUNT(l.id) AS total')
            ->leftJoin('l.origin', 'o')
            ->leftJoin('o.domain', 'd');

        $this->applyScope($qb, $scope);

        return $qb
            ->groupBy('l.fingerprint')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Retourne le volume de logs par jour sur une fenetre glissante.
     *
     * @param array{domain?: string, client?: string|null, version?: string|null} $scope
     * @return list<array{day: mixed, total: mixed}>
     */
    public function countByDay(array $scope, int $days = 30): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select("DATE(l.ts) AS day, COUNT(l.id) AS total")
            ->leftJoin('l.origin', 'o')
            ->leftJoin('o.domain', 'd');

        $this->applyScope($qb, $scope);

        return $qb
            ->andWhere('l.ts >= :date')
            ->setParameter('date', new \DateTimeImmutable("-$days days"))
            ->groupBy('day')
            ->orderBy('day', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Retourne les derniers logs d'un utilisateur dans un scope donne.
     *
     * @param array{domain?: string, client?: string|null, version?: string|null} $scope
     * @return list<LogEvent>
     */
    public function findByUser(array $scope, int $userId, int $limit = 100): array
    {
        $qb = $this->baseQb();

        $this->applyScope($qb, $scope);

        return $qb
            ->andWhere('l.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('l.ts', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
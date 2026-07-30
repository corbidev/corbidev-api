<?php

declare(strict_types=1);

namespace App\Auth\Entity;

use App\Auth\Repository\ApiClientScopeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Représente une permission accordée à un client API.
 *
 * Une instance correspond à un scope unique autorisé pour un client.
 */
#[ORM\Entity(repositoryClass: ApiClientScopeRepository::class)]
#[ORM\Table(name: 'auth_api_client_scope')]
#[ORM\UniqueConstraint(
    name: 'uniq_auth_api_client_scope',
    columns: ['api_client_id', 'scope']
)]
class ApiClientScope
{
    /**
     * Identifiant technique unique.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    /**
     * Identifiant permettant de corréler les opérations métier.
     */
    #[ORM\Column(type: 'uuid')]
    private Uuid $correlationId;

    /**
     * Client auquel appartient le scope.
     */
    #[ORM\ManyToOne(targetEntity: ApiClient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ApiClient $apiClient;

    /**
     * Nom du scope autorisé.
     *
     * Exemples :
     * - auth.token
     * - users.read
     * - billing.write
     */
    #[ORM\Column(length: 100)]
    private string $scope;

    /**
     * Date de création.
     */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * Initialise un nouveau scope.
     */
    public function __construct(
        ApiClient $apiClient,
        string $scope,
        Uuid $correlationId,
    ) {
        $this->id = Uuid::v7();
        $this->apiClient = $apiClient;
        $this->scope = $scope;
        $this->correlationId = $correlationId;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Retourne l'identifiant.
     */
    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * Retourne l'identifiant de corrélation.
     */
    public function getCorrelationId(): Uuid
    {
        return $this->correlationId;
    }

    /**
     * Retourne le client propriétaire.
     */
    public function getApiClient(): ApiClient
    {
        return $this->apiClient;
    }

    /**
     * Retourne le scope.
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
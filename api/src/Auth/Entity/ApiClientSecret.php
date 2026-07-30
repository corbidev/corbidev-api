<?php

declare(strict_types=1);

namespace App\Auth\Entity;

use App\Auth\Repository\ApiClientSecretRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Représente un secret utilisé par un client API pour s'authentifier.
 *
 * Un client peut posséder plusieurs secrets afin de permettre leur rotation
 * sans interruption de service.
 */
#[ORM\Entity(repositoryClass: ApiClientSecretRepository::class)]
#[ORM\Table(name: 'auth_api_client_secret')]
class ApiClientSecret
{
    /**
     * Identifiant technique unique du secret.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    /**
     * Identifiant de corrélation permettant de relier les opérations métier.
     */
    #[ORM\Column(type: 'uuid')]
    private Uuid $correlationId;

    /**
     * Client propriétaire du secret.
     */
    #[ORM\ManyToOne(targetEntity: ApiClient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ApiClient $apiClient;

    /**
     * Empreinte (hash) du secret.
     *
     * Le secret en clair n'est jamais stocké.
     */
    #[ORM\Column(length: 255)]
    private string $secretHash;

    /**
     * Date de création.
     */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière modification.
     */
    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * Date de dernière utilisation.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * Date d'expiration du secret.
     *
     * Null signifie que le secret n'expire pas automatiquement.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    /**
     * Date de révocation du secret.
     *
     * Null signifie que le secret est toujours actif.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    /**
     * Initialise un nouveau secret.
     */
    public function __construct(
        ApiClient $apiClient,
        string $secretHash,
        Uuid $correlationId,
    ) {
        $now = new \DateTimeImmutable();

        $this->id = Uuid::v7();
        $this->apiClient = $apiClient;
        $this->secretHash = $secretHash;
        $this->correlationId = $correlationId;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Retourne l'identifiant du secret.
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
     * Associe une opération métier au secret.
     */
    public function correlate(Uuid $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * Retourne le client propriétaire.
     */
    public function getApiClient(): ApiClient
    {
        return $this->apiClient;
    }

    /**
     * Retourne le hash du secret.
     */
    public function getSecretHash(): string
    {
        return $this->secretHash;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Retourne la date de dernière modification.
     */
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Retourne la date de dernière utilisation.
     */
    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * Retourne la date d'expiration.
     */
    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Définit une date d'expiration.
     */
    public function expireAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        $this->touch();

        return $this;
    }

    /**
     * Retourne la date de révocation.
     */
    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    /**
     * Révoque définitivement le secret.
     */
    public function revoke(): self
    {
        $this->revokedAt = new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    /**
     * Enregistre une utilisation réussie du secret.
     */
    public function markAsUsed(): self
    {
        $this->lastUsedAt = new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    /**
     * Indique si le secret a été révoqué.
     */
    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    /**
     * Indique si le secret est expiré.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt !== null
            && $this->expiresAt <= new \DateTimeImmutable();
    }

    /**
     * Indique si le secret peut être utilisé.
     */
    public function isValid(): bool
    {
        return !$this->isRevoked() && !$this->isExpired();
    }

    /**
     * Met à jour la date de dernière modification.
     */
    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
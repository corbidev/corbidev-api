<?php

declare(strict_types=1);

namespace App\Auth\Entity;

use App\Auth\Repository\ApiClientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ApiClientRepository::class)]
#[ORM\Table(name: 'auth_api_client')]
class ApiClient
{
    /**
     * Identifiant technique unique du client API.
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
     * Nom lisible du client.
     */
    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * Identifiant public utilisé lors de l'authentification.
     */
    #[ORM\Column(length: 100, unique: true)]
    private string $clientId;


    /**
     * Indique si le client est autorisé à appeler l'API.
     */
    #[ORM\Column]
    private bool $enabled = true;


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
     * Date du dernier appel authentifié.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * Initialise un nouveau client API.
     */
    public function __construct(
        string $name,
        string $clientId,
        Uuid $correlationId,
    ) {
        $now = new \DateTimeImmutable();

        $this->id = Uuid::v7();
        $this->correlationId = $correlationId;
        $this->name = $name;
        $this->clientId = $clientId;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Retourne l'identifiant du client.
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
     * Associe une opération métier au client.
     */
    public function correlate(Uuid $correlationId): self
    {
        $this->correlationId = $correlationId;

        return $this;
    }

    /**
     * Retourne le nom du client.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Renomme le client.
     */
    public function rename(string $name): self
    {
        $this->name = $name;
        $this->touch();

        return $this;
    }

    /**
     * Retourne le clientId.
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Indique si le client est actif.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Active le client.
     */
    public function enable(): self
    {
        $this->enabled = true;

        $this->touch();

        return $this;
    }

    /**
     * Désactive le client.
     */
    public function disable(): self
    {
        $this->enabled = false;

        $this->touch();

        return $this;
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
     * Retourne la date du dernier appel.
     */
    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * Enregistre une utilisation réussie du client.
     */
    public function markAsUsed(): self
    {
        $now = new \DateTimeImmutable();

        $this->lastUsedAt = $now;
        $this->updatedAt = $now;

        return $this;
    }

    /**
     * Met à jour la date de modification.
     */
    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
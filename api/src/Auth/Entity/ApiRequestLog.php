<?php

declare(strict_types=1);

namespace App\Auth\Entity;

use App\Auth\Repository\ApiRequestLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Représente une requête authentifiée reçue par l'API.
 *
 * Cette entité permet de conserver un historique des appels afin de
 * faciliter l'audit, le diagnostic et la traçabilité.
 */
#[ORM\Entity(repositoryClass: ApiRequestLogRepository::class)]
#[ORM\Table(name: 'auth_api_request')]
class ApiRequestLog
{
    /**
     * Identifiant technique unique de la requête.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    /**
     * Identifiant permettant de corréler plusieurs opérations métier.
     */
    #[ORM\Column(type: 'uuid')]
    private Uuid $correlationId;

    /**
     * Client ayant effectué la requête.
     */
    #[ORM\ManyToOne(targetEntity: ApiClient::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ApiClient $apiClient;

    /**
     * Secret utilisé pour authentifier la requête.
     */
    #[ORM\ManyToOne(targetEntity: ApiClientSecret::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ApiClientSecret $apiClientSecret;

    /**
     * Identifiant unique de la requête HTTP.
     */
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $requestId;

    /**
     * Méthode HTTP utilisée.
     */
    #[ORM\Column(length: 10)]
    private string $method;

    /**
     * URI appelée.
     */
    #[ORM\Column(length: 2048)]
    private string $uri;

    /**
     * Adresse IP du client.
     */
    #[ORM\Column(length: 45)]
    private string $ipAddress;

    /**
     * User-Agent transmis par le client.
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $userAgent = null;

    /**
     * Code HTTP retourné.
     */
    #[ORM\Column]
    private int $statusCode;

    /**
     * Date de création de la requête.
     */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * Initialise une nouvelle requête authentifiée.
     */
    public function __construct(
        ApiClient $apiClient,
        ApiClientSecret $apiClientSecret,
        Uuid $correlationId,
        Uuid $requestId,
        string $method,
        string $uri,
        string $ipAddress,
        int $statusCode,
        ?string $userAgent = null,
    ) {
        $this->id = Uuid::v7();
        $this->correlationId = $correlationId;
        $this->apiClient = $apiClient;
        $this->apiClientSecret = $apiClientSecret;
        $this->requestId = $requestId;
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->statusCode = $statusCode;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Retourne l'identifiant de la requête.
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
     * Retourne le client ayant effectué la requête.
     */
    public function getApiClient(): ApiClient
    {
        return $this->apiClient;
    }

    /**
     * Retourne le secret utilisé.
     */
    public function getApiClientSecret(): ApiClientSecret
    {
        return $this->apiClientSecret;
    }

    /**
     * Retourne l'identifiant unique de la requête HTTP.
     */
    public function getRequestId(): Uuid
    {
        return $this->requestId;
    }

    /**
     * Retourne la méthode HTTP.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Retourne l'URI appelée.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Retourne l'adresse IP du client.
     */
    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * Retourne le User-Agent.
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Retourne le code HTTP.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
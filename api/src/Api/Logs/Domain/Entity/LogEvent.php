<?php

namespace App\Api\Logs\Domain\Entity;

use App\Api\Logs\Infrastructure\Repository\LogEventRepository;
use Doctrine\ORM\Mapping as ORM;

use App\Api\Logs\Domain\Entity\LogEnv;
use App\Api\Logs\Domain\Entity\LogLevel;
use App\Api\Logs\Domain\Entity\LogErrorCode;

#[ORM\Entity(repositoryClass: LogEventRepository::class)]
#[ORM\Table(name: 'CBV_LOGS_EVENT')]
#[ORM\UniqueConstraint(name: 'uniq_external_id', columns: ['externalId'])]
#[ORM\Index(columns: ['ts'], name: 'idx_log_ts')]
#[ORM\Index(columns: ['fingerprint'], name: 'idx_log_fingerprint')]
#[ORM\Index(columns: ['domain', 'levelName'], name: 'idx_domain_level')]
class LogEvent
{
    public function __construct()
    {
        $this->ts = new \DateTimeImmutable();
    }

    // -------------------------
    // IDENTIFIANT
    // -------------------------

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    /**
     * UUID fourni par le client (idempotence)
     */
    #[ORM\Column(length: 36, unique: true)]
    private string $externalId;

    // -------------------------
    // TEMPS
    // -------------------------

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $ts;

    // -------------------------
    // RELATIONS (lookup tables)
    // -------------------------

    #[ORM\ManyToOne(targetEntity: LogLevel::class)]
    #[ORM\JoinColumn(nullable: false)]
    private LogLevel $level;

    #[ORM\ManyToOne(targetEntity: LogEnv::class)]
    #[ORM\JoinColumn(nullable: false)]
    private LogEnv $env;

    #[ORM\ManyToOne(targetEntity: LogErrorCode::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?LogErrorCode $errorCodeEntity = null;

    // -------------------------
    // DONNÉES DUPLIQUÉES (PERF)
    // -------------------------

    #[ORM\Column(length: 50)]
    private string $levelName = '';

    #[ORM\Column(length: 50)]
    private string $envName = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $domain = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $uri = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $method = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $client = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $version = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $errorCode = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    // -------------------------
    // DONNÉES PRINCIPALES
    // -------------------------

    #[ORM\Column(length: 1024)]
    private string $message;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $httpStatus = null;

    #[ORM\Column(length: 64)]
    private string $fingerprint;

    // -------------------------
    // GETTERS
    // -------------------------

    public function getId(): ?int { return $this->id; }
    public function getExternalId(): string { return $this->externalId; }
    public function getTs(): \DateTimeImmutable { return $this->ts; }
    public function getLevel(): LogLevel { return $this->level; }
    public function getEnv(): LogEnv { return $this->env; }
    public function getErrorCodeEntity(): ?LogErrorCode { return $this->errorCodeEntity; }

    public function getDomain(): ?string { return $this->domain; }
    public function getUri(): ?string { return $this->uri; }
    public function getMethod(): ?string { return $this->method; }
    public function getClient(): ?string { return $this->client; }
    public function getVersion(): ?string { return $this->version; }
    public function getContext(): ?array { return $this->context; }

    public function getMessage(): string { return $this->message; }
    public function getUserId(): ?int { return $this->userId; }
    public function getHttpStatus(): ?int { return $this->httpStatus; }
    public function getFingerprint(): string { return $this->fingerprint; }

    // -------------------------
    // SETTERS
    // -------------------------

    public function setExternalId(string $externalId): self
    {
        $externalId = trim($externalId);

        if ($externalId === '') {
            throw new \InvalidArgumentException('ExternalId cannot be empty');
        }

        $this->externalId = $externalId;

        return $this;
    }

    public function setLevel(LogLevel $level): self
    {
        $this->level = $level;
        $this->levelName = $level->getName();

        return $this;
    }

    public function setEnv(LogEnv $env): self
    {
        $this->env = $env;
        $this->envName = $env->getName();

        return $this;
    }

    public function setErrorCodeEntity(?LogErrorCode $errorCode): self
    {
        $this->errorCodeEntity = $errorCode;
        $this->errorCode = $errorCode?->getCode();

        return $this;
    }

    public function setDomain(?string $domain): self
    {
        $this->domain = $domain ? strtolower(trim($domain)) : null;
        return $this;
    }

    public function setUri(?string $uri): self
    {
        $this->uri = $uri ? trim($uri) : null;
        return $this;
    }

    public function setMethod(?string $method): self
    {
        $this->method = $method ? strtoupper(trim($method)) : null;
        return $this;
    }

    public function setClient(?string $client): self
    {
        $this->client = $client ? trim($client) : null;
        return $this;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version ? trim($version) : null;
        return $this;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function setMessage(string $message): self
    {
        $message = trim($message);

        if ($message === '') {
            throw new \InvalidArgumentException('Message cannot be empty');
        }

        $this->message = $message;
        return $this;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function setHttpStatus(?int $httpStatus): self
    {
        $this->httpStatus = $httpStatus;
        return $this;
    }

    public function setFingerprint(string $fingerprint): self
    {
        $fingerprint = trim($fingerprint);

        if ($fingerprint === '') {
            throw new \InvalidArgumentException('Fingerprint cannot be empty');
        }

        $this->fingerprint = $fingerprint;
        return $this;
    }

    // -------------------------
    // MÉTIER
    // -------------------------

    public function isError(): bool
    {
        return in_array(
            $this->levelName,
            ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'],
            true
        );
    }

    // -------------------------
    // UTILS
    // -------------------------

    public function __toString(): string
    {
        return sprintf('%s: %s', $this->levelName ?: '?', $this->message ?: '');
    }
}
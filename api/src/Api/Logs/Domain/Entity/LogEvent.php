<?php

namespace App\Api\Logs\Domain\Entity;

use App\Api\Logs\Infrastructure\Repository\LogEventRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

#[ApiResource]
#[Post]
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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    // 🔥 UUID CLIENT
    #[ORM\Column(length: 36, unique: true)]
    private string $externalId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $ts;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private LogLevel $level;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private LogEnv $env;

    #[ORM\ManyToOne]
    private ?LogErrorCode $errorCodeEntity = null;

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

    #[ORM\Column(length: 1024)]
    private string $message;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $httpStatus = null;

    #[ORM\Column(length: 64)]
    private string $fingerprint;

    // -------------------------
    // GETTERS / SETTERS
    // -------------------------

    public function getId(): ?int { return $this->id; }

    public function getExternalId(): string { return $this->externalId; }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = trim($externalId);
        return $this;
    }

    public function getTs(): \DateTimeImmutable { return $this->ts; }

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
}
<?php

namespace App\Entity\Log;

use App\Repository\Log\LogEntryRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogEntryRepository::class)]
#[ORM\Table(name: 'log_entry')]
#[ORM\Index(name: 'idx_ts', columns: ['ts'])]
#[ORM\Index(name: 'idx_level', columns: ['level_id'])]
#[ORM\Index(name: 'idx_source', columns: ['source_id'])]
#[ORM\Index(name: 'idx_env', columns: ['env_id'])]
#[ORM\Index(name: 'idx_fingerprint', columns: ['fingerprint'])]
#[ORM\Index(name: 'idx_level_ts', columns: ['level_id', 'ts'])]
#[ORM\Index(name: 'idx_source_ts', columns: ['source_id', 'ts'])]
#[ORM\Index(name: 'idx_env_ts', columns: ['env_id', 'ts'])]
class LogEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable', precision: 6)]
    private ?DateTimeImmutable $ts = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'level_id', referencedColumnName: 'id', nullable: false)]
    private ?LogLevel $level = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'source_id', referencedColumnName: 'id', nullable: false)]
    private ?LogSource $source = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'env_id', referencedColumnName: 'id', nullable: false)]
    private ?LogEnv $env = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'route_id', referencedColumnName: 'id', nullable: true)]
    private ?LogRoute $route = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(name: 'http_status', type: 'smallint', nullable: true)]
    private ?int $httpStatus = null;

    #[ORM\Column(name: 'duration_ms', nullable: true)]
    private ?int $durationMs = null;

    #[ORM\Column(length: 64, nullable: true, options: ['fixed' => true])]
    private ?string $fingerprint = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', precision: 6)]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, LogEntryTag>
     */
    #[ORM\OneToMany(mappedBy: 'logEntry', targetEntity: LogEntryTag::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $entryTags;

    public function __construct()
    {
        $this->entryTags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTs(): ?DateTimeImmutable
    {
        return $this->ts;
    }

    public function setTs(DateTimeImmutable $ts): static
    {
        $this->ts = $ts;

        return $this;
    }

    public function getLevel(): ?LogLevel
    {
        return $this->level;
    }

    public function setLevel(?LogLevel $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getSource(): ?LogSource
    {
        return $this->source;
    }

    public function setSource(?LogSource $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getEnv(): ?LogEnv
    {
        return $this->env;
    }

    public function setEnv(?LogEnv $env): static
    {
        $this->env = $env;

        return $this;
    }

    public function getRoute(): ?LogRoute
    {
        return $this->route;
    }

    public function setRoute(?LogRoute $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function setHttpStatus(?int $httpStatus): static
    {
        $this->httpStatus = $httpStatus;

        return $this;
    }

    public function getDurationMs(): ?int
    {
        return $this->durationMs;
    }

    public function setDurationMs(?int $durationMs): static
    {
        $this->durationMs = $durationMs;

        return $this;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(?string $fingerprint): static
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, LogEntryTag>
     */
    public function getEntryTags(): Collection
    {
        return $this->entryTags;
    }

    public function addEntryTag(LogEntryTag $entryTag): static
    {
        if (!$this->entryTags->contains($entryTag)) {
            $this->entryTags->add($entryTag);
            $entryTag->setLogEntry($this);
        }

        return $this;
    }

    public function removeEntryTag(LogEntryTag $entryTag): static
    {
        if ($this->entryTags->removeElement($entryTag) && $entryTag->getLogEntry() === $this) {
            $entryTag->setLogEntry(null);
        }

        return $this;
    }
}

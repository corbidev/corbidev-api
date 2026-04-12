<?php

namespace App\RessLogs\Entity;

use App\RessLogs\Entity\LogEntry;
use App\RessLogs\Repository\LogSourceRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogSourceRepository::class)]
#[ORM\Table(name: 'log_source')]
class LogSource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(name: 'api_key', length: 64, unique: true)]
    private ?string $apiKey = null;

    #[ORM\Column(name: 'client_secret', length: 255, nullable: true)]
    private ?string $clientSecret = null;

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, LogEntry>
     */
    #[ORM\OneToMany(mappedBy: 'source', targetEntity: LogEntry::class)]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): static
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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
     * @return Collection<int, LogEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(LogEntry $entry): static
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setSource($this);
        }

        return $this;
    }

    public function removeEntry(LogEntry $entry): static
    {
        if ($this->entries->removeElement($entry) && $entry->getSource() === $this) {
            $entry->setSource(null);
        }

        return $this;
    }
}

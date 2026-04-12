<?php

namespace App\RessLogs\Entity;

use App\RessLogs\Entity\LogUri;
use App\RessLogs\Repository\LogUrlRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogUrlRepository::class)]
#[ORM\Table(name: 'log_url')]
class LogUrl
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 768, unique: true)]
    private ?string $url = null;

    /**
     * @var Collection<int, LogUri>
     */
    #[ORM\OneToMany(mappedBy: 'url', targetEntity: LogUri::class)]
    private Collection $uris;

    /**
     * @var Collection<int, LogEntry>
     */
    #[ORM\OneToMany(mappedBy: 'url', targetEntity: LogEntry::class)]
    private Collection $entries;

    public function __construct()
    {
        $this->uris = new ArrayCollection();
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Collection<int, LogUri>
     */
    public function getUris(): Collection
    {
        return $this->uris;
    }

    public function addUri(LogUri $uri): static
    {
        if (!$this->uris->contains($uri)) {
            $this->uris->add($uri);
            $uri->setUrl($this);
        }

        return $this;
    }

    public function removeUri(LogUri $uri): static
    {
        if ($this->uris->removeElement($uri) && $uri->getUrl() === $this) {
            $uri->setUrl(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, LogEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }
}

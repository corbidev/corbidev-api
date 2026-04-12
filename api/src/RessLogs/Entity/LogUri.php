<?php

namespace App\RessLogs\Entity;

use App\RessLogs\Repository\LogUriRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogUriRepository::class)]
#[ORM\Table(name: 'log_uri')]
class LogUri
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $uri = null;

    #[ORM\ManyToOne(inversedBy: 'uris')]
    #[ORM\JoinColumn(name: 'url_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?LogUrl $url = null;

    /**
     * @var Collection<int, LogEntry>
     */
    #[ORM\OneToMany(mappedBy: 'uri', targetEntity: LogEntry::class)]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setUri(string $uri): static
    {
        $this->uri = $uri;

        return $this;
    }

    public function getUrl(): ?LogUrl
    {
        return $this->url;
    }

    public function setUrl(?LogUrl $url): static
    {
        $this->url = $url;

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

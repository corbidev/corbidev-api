<?php

namespace App\RessLogs\Entity;

use App\RessLogs\Entity\LogEntry;
use App\RessLogs\Repository\LogRouteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogRouteRepository::class)]
#[ORM\Table(name: 'log_route')]
class LogRoute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $uri = null;

    /**
     * @var Collection<int, LogEntry>
     */
    #[ORM\OneToMany(mappedBy: 'route', targetEntity: LogEntry::class)]
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
            $entry->setRoute($this);
        }

        return $this;
    }

    public function removeEntry(LogEntry $entry): static
    {
        if ($this->entries->removeElement($entry) && $entry->getRoute() === $this) {
            $entry->setRoute(null);
        }

        return $this;
    }
}

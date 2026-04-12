<?php

namespace App\RessLogs\Entity;

use App\RessLogs\Entity\LogEntry;
use App\RessLogs\Repository\LogLevelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogLevelRepository::class)]
#[ORM\Table(name: 'log_level')]
class LogLevel
{
    #[ORM\Id]
    #[ORM\Column(type: 'smallint')]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $name = null;

    /**
     * @var Collection<int, LogEntry>
     */
    #[ORM\OneToMany(mappedBy: 'level', targetEntity: LogEntry::class)]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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
            $entry->setLevel($this);
        }

        return $this;
    }

    public function removeEntry(LogEntry $entry): static
    {
        if ($this->entries->removeElement($entry) && $entry->getLevel() === $this) {
            $entry->setLevel(null);
        }

        return $this;
    }
}

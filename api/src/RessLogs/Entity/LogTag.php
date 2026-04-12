<?php

namespace App\RessLogs\Entity;

use App\RessLogs\Entity\LogEntryTag;
use App\RessLogs\Repository\LogTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogTagRepository::class)]
#[ORM\Table(name: 'log_tag')]
#[ORM\UniqueConstraint(name: 'name', columns: ['name'])]
class LogTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * @var Collection<int, LogEntryTag>
     */
    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: LogEntryTag::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $entryTags;

    public function __construct()
    {
        $this->entryTags = new ArrayCollection();
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
            $entryTag->setTag($this);
        }

        return $this;
    }

    public function removeEntryTag(LogEntryTag $entryTag): static
    {
        if ($this->entryTags->removeElement($entryTag) && $entryTag->getTag() === $this) {
            $entryTag->setTag(null);
        }

        return $this;
    }
}

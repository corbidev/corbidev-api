<?php
namespace App\Api\Logs\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_LEVEL')]
/**
 * Niveau de severite d'un evenement de log.
 */
class LogLevel {
    /**
     * Identifiant technique du niveau.
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'smallint')]
    private ?int $id = null;

    /**
     * Libelle court unique, par exemple INFO ou ERROR.
     */
    #[ORM\Column(length: 10, unique: true)]
    private string $name;

    /**
     * Valeur numerique du niveau pour le tri et la comparaison.
     */
    #[ORM\Column(type: 'smallint', unique: true)]
    private int $levelInt;

    #[ORM\OneToMany(mappedBy: 'level', targetEntity: LogEvent::class)]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = strtoupper(trim($name));

        return $this;
    }

    public function getLevelInt(): int
    {
        return $this->levelInt;
    }

    public function setLevelInt(int $levelInt): self
    {
        $this->levelInt = $levelInt;

        return $this;
    }

    public function isHigherOrEqualThan(int $levelInt): bool
    {
        return $this->levelInt >= $levelInt;
    }

    /**
     * @return Collection<int, LogEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(LogEvent $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
        }

        return $this;
    }

    public function removeEvent(LogEvent $event): self
    {
        $this->events->removeElement($event);

        return $this;
    }

    public function __toString(): string
    {
        return isset($this->name) ? $this->name : '';
    }
}

<?php
namespace App\Api\Logs\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_ENV')]
/**
 * Environnement d'execution associe a un evenement de log.
 */
class LogEnv {
    /**
     * Identifiant technique de l'environnement.
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'smallint')]
    private ?int $id = null;

    /**
     * Code fonctionnel de l'environnement, par exemple prod ou dev.
     */
    #[ORM\Column(length: 10, unique: true)]
    private string $name;

    #[ORM\OneToMany(mappedBy: 'env', targetEntity: LogEvent::class)]
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
        $this->name = trim($name);

        return $this;
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

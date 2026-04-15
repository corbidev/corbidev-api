<?php
namespace App\Logs\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_ERROR_CODE')]
/**
 * Code d'erreur metier rattache a un evenement de log.
 */
class LogErrorCode {
    /**
     * Identifiant technique du code d'erreur.
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'smallint')]
    private ?int $id = null;

    /**
     * Code court unique expose par l'application.
     */
    #[ORM\Column(length: 100, unique: true)]
    private string $code;

    /**
     * Description lisible du code d'erreur.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'errorCode', targetEntity: LogEvent::class)]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = trim($code);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description !== null ? trim($description) : null;

        return $this;
    }

    public function hasDescription(): bool
    {
        return $this->description !== null && $this->description !== '';
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
        if ($this->events->removeElement($event) && $event->getErrorCode() === $this) {
            $event->setErrorCode(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return isset($this->code) ? $this->code : '';
    }
}

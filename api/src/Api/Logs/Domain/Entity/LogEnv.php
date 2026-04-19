namespace App\Api\Logs\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_ENV')]
#[ORM\UniqueConstraint(name: 'uniq_log_env_name', columns: ['name'])]
class LogEnv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'smallint')]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private string $name;

    #[ORM\OneToMany(mappedBy: 'env', targetEntity: LogEvent::class)]
    private Collection $events;

    public function __construct(string $name)
    {
        $this->setName($name);
        $this->events = new ArrayCollection();
    }

    // -------------------------
    // GETTERS
    // -------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, LogEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    // -------------------------
    // LOGIQUE MÉTIER
    // -------------------------

    public function setName(string $name): self
    {
        $name = strtolower(trim($name));

        if ($name === '') {
            throw new \InvalidArgumentException('Environment name cannot be empty');
        }

        $this->name = $name;

        return $this;
    }

    public function addEvent(LogEvent $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setEnv($this); // 🔥 synchronisation bidirectionnelle
        }

        return $this;
    }

    public function removeEvent(LogEvent $event): self
    {
        if ($this->events->removeElement($event)) {
            if ($event->getEnv() === $this) {
                $event->setEnv(null);
            }
        }

        return $this;
    }

    // -------------------------
    // UTILS
    // -------------------------

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

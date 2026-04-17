<?php
namespace App\Api\Logs\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_EVENT_DETAIL')]
/**
 * Charge utile detaillee associee a un evenement de log.
 */
class LogEventDetail {
    /**
     * Identifiant technique du detail.
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    /**
     * Evenement principal auquel le detail appartient.
     */
    #[ORM\OneToOne(inversedBy: 'detail'), ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?LogEvent $logEvent = null;

    /**
     * Contexte brut serialise en JSON.
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogEvent(): ?LogEvent
    {
        return $this->logEvent;
    }

    public function setLogEvent(?LogEvent $logEvent): self
    {
        if ($this->logEvent === $logEvent) {
            return $this;
        }

        $previousLogEvent = $this->logEvent;
        $this->logEvent = $logEvent;

        if ($previousLogEvent !== null && $previousLogEvent->getDetail() === $this) {
            $previousLogEvent->setDetail(null);
        }

        if ($logEvent !== null && $logEvent->getDetail() !== $this) {
            $logEvent->setDetail($this);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed>|null $context
     */
    public function setContext(?array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function hasContext(): bool
    {
        return !empty($this->context);
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }

    public function __toString(): string
    {
        if (!isset($this->logEvent)) {
            return '';
        }

        return sprintf('detail:%s', (string) ($this->logEvent->getId() ?? 'new'));
    }
}
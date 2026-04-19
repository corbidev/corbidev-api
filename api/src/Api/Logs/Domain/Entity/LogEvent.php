<?php
namespace App\Api\Logs\Domain\Entity;

use App\Api\Logs\Infrastructure\Repository\LogEventRepository;
use App\Api\Logs\Application\Dto\CreateLogEventDto;
use App\Api\Logs\Application\Processor\CreateLogProcessor;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

#[ApiResource]
#[Post(
    input: CreateLogEventDto::class,
    processor: CreateLogProcessor::class
)]
#[ORM\Entity(repositoryClass: LogEventRepository::class)]
#[ORM\Table(name: 'CBV_LOGS_EVENT')]
/**
 * Evenement principal enregistre par le systeme de logs.
 */
class LogEvent {

   public function __construct()
   {
        $this->ts = new \DateTimeImmutable();
   }
    /**
     * Identifiant technique de l'evenement.
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    /**
     * Horodatage immutable de creation du log.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $ts;

    /**
     * Niveau de severite associe a l'evenement.
     */
    #[ORM\ManyToOne(inversedBy: 'events'), ORM\JoinColumn(nullable: false)]
    private LogLevel $level;

    /**
     * Environnement d'execution ayant emis l'evenement.
     */
    #[ORM\ManyToOne(inversedBy: 'events'), ORM\JoinColumn(nullable: false)]
    private LogEnv $env;

    /**
     * Origine applicative detaillee de l'evenement.
     */
    #[ORM\ManyToOne(inversedBy: 'events'), ORM\JoinColumn(nullable: false)]
    private LogOrigin $origin;

    /**
     * Code d'erreur optionnel associe a l'evenement.
     */
    #[ORM\ManyToOne(inversedBy: 'events')]
    private ?LogErrorCode $errorCode = null;

    #[ORM\OneToOne(mappedBy: 'logEvent', targetEntity: LogEventDetail::class)]
    private ?LogEventDetail $detail = null;

    /**
     * Message fonctionnel ou technique du log.
     */
    #[ORM\Column(length: 1024)]
    private string $message;

    /**
     * Identifiant utilisateur associe a l'evenement quand il existe.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $userId = null;

    /**
     * Code HTTP associe a l'evenement quand il provient d'un contexte web.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $httpStatus = null;

    /**
     * Empreinte de deduplication pour les regroupements statistiques.
     */
    #[ORM\Column(length: 64)]
    private string $fingerprint;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTs(): \DateTimeImmutable
    {
        return $this->ts;
    }

    public function setTs(\DateTimeImmutable $ts): self
    {
        $this->ts = $ts;

        return $this;
    }

    public function getLevel(): LogLevel
    {
        return $this->level;
    }

    public function setLevel(LogLevel $level): self
    {
        if (isset($this->level) && $this->level !== $level) {
            $this->level->removeEvent($this);
        }

        $this->level = $level;

        $level->addEvent($this);

        return $this;
    }

    public function getEnv(): LogEnv
    {
        return $this->env;
    }

    public function setEnv(LogEnv $env): self
    {
        if (isset($this->env) && $this->env !== $env) {
            $this->env->removeEvent($this);
        }

        $this->env = $env;

        $env->addEvent($this);

        return $this;
    }

    public function getOrigin(): LogOrigin
    {
        return $this->origin;
    }

    public function setOrigin(LogOrigin $origin): self
    {
        if (isset($this->origin) && $this->origin !== $origin) {
            $this->origin->removeEvent($this);
        }

        $this->origin = $origin;

        $origin->addEvent($this);

        return $this;
    }

    public function getErrorCode(): ?LogErrorCode
    {
        return $this->errorCode;
    }

    public function setErrorCode(?LogErrorCode $errorCode): self
    {
        if ($this->errorCode !== null && $this->errorCode !== $errorCode) {
            $this->errorCode->removeEvent($this);
        }

        $this->errorCode = $errorCode;

        if ($errorCode !== null) {
            $errorCode->addEvent($this);
        }

        return $this;
    }

    public function getDetail(): ?LogEventDetail
    {
        return $this->detail;
    }

    public function setDetail(?LogEventDetail $detail): self
    {
        if ($this->detail === $detail) {
            return $this;
        }

        $previousDetail = $this->detail;
        $this->detail = $detail;

        if ($previousDetail !== null && $previousDetail->getLogEvent() === $this) {
            $previousDetail->setLogEvent(null);
        }

        if ($detail !== null && $detail->getLogEvent() !== $this) {
            $detail->setLogEvent($this);
        }

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = trim($message);

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function setHttpStatus(?int $httpStatus): self
    {
        $this->httpStatus = $httpStatus;

        return $this;
    }

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(string $fingerprint): self
    {
        $this->fingerprint = trim($fingerprint);

        return $this;
    }

    public function hasErrorCode(): bool
    {
        return $this->errorCode !== null;
    }

    public function isErrorLevel(): bool
    {
        if (!isset($this->level)) {
            return false;
        }

        return in_array($this->level->getName(), ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true);
    }

    /**
     * @return array{domain: string, client: string, version: string}
     */
    public function getScope(): array
    {
        return $this->origin->getScope();
    }

    public function __toString(): string
    {
        if (!isset($this->message)) {
            return '';
        }

        if (!isset($this->level)) {
            return $this->message;
        }

        return sprintf('%s: %s', $this->level->getName(), $this->message);
    }
}
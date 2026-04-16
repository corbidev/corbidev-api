<?php
namespace App\Api\Logs\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_ORIGIN')]
/**
 * Origine applicative d'un evenement de log.
 */
class LogOrigin {
    /**
     * Identifiant technique de l'origine.
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    /**
     * Domaine tenant associe a l'origine.
     */
    #[ORM\ManyToOne(inversedBy: 'origins'), ORM\JoinColumn(nullable: false)]
    private LogDomain $domain;

    /**
     * URI appelee par la requete source.
     */
    #[ORM\ManyToOne(inversedBy: 'origins'), ORM\JoinColumn(nullable: false)]
    private LogUri $uri;

    /**
     * Methode HTTP de la requete source.
     */
    #[ORM\Column(length: 10)]
    private string $method;

    /**
     * Identifiant du client applicatif emetteur.
     */
    #[ORM\Column(length: 50)]
    private string $client;

    /**
     * Version du client applicatif emetteur.
     */
    #[ORM\Column(length: 20)]
    private string $version;

    #[ORM\OneToMany(mappedBy: 'origin', targetEntity: LogEvent::class)]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): LogDomain
    {
        return $this->domain;
    }

    public function setDomain(LogDomain $domain): self
    {
        if (isset($this->domain) && $this->domain !== $domain) {
            $this->domain->removeOrigin($this);
        }

        $this->domain = $domain;

        $domain->addOrigin($this);

        return $this;
    }

    public function getUri(): LogUri
    {
        return $this->uri;
    }

    public function setUri(LogUri $uri): self
    {
        if (isset($this->uri) && $this->uri !== $uri) {
            $this->uri->removeOrigin($this);
        }

        $this->uri = $uri;

        $uri->addOrigin($this);

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = strtoupper(trim($method));

        return $this;
    }

    public function getClient(): string
    {
        return $this->client;
    }

    public function setClient(string $client): self
    {
        $this->client = trim($client);

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = trim($version);

        return $this;
    }

    /**
     * @return array{domain: string, client: string, version: string}
     */
    public function getScope(): array
    {
        return [
            'domain' => $this->domain->getUrl(),
            'client' => $this->client,
            'version' => $this->version,
        ];
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
        if (!isset($this->method, $this->uri)) {
            return '';
        }

        return sprintf('%s %s', $this->method, $this->uri->getUri());
    }
}

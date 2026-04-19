<?php
namespace App\Api\Logs\Domain\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_URI')]
/**
 * URI normalisée ciblée par un evenement de log.
 */
class LogUri {
    /**
     * Identifiant technique de l'URI.
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    /**
     * Chemin unique de la ressource appelee.
     */
    #[ORM\Column(length: 255, unique: true)]
    private string $uri;

    #[ORM\OneToMany(mappedBy: 'uri', targetEntity: LogOrigin::class)]
    private Collection $origins;

    public function __construct()
    {
        $this->origins = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): self
    {
        $this->uri = trim($uri);

        return $this;
    }

    /**
     * @return Collection<int, LogOrigin>
     */
    public function getOrigins(): Collection
    {
        return $this->origins;
    }

    public function addOrigin(LogOrigin $origin): self
    {
        if (!$this->origins->contains($origin)) {
            $this->origins->add($origin);
        }

        return $this;
    }

    public function removeOrigin(LogOrigin $origin): self
    {
        $this->origins->removeElement($origin);

        return $this;
    }

    public function __toString(): string
    {
        return isset($this->uri) ? $this->uri : '';
    }
}
<?php
namespace App\Api\Logs\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_DOMAIN')]
/**
 * Domaine fonctionnel rattache a une origine de log.
 */
class LogDomain {
    /**
     * Identifiant technique du domaine.
     */
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'bigint')]
    private ?int $id = null;

    /**
     * URL canonique utilisee pour le scoping tenant.
     */
    #[ORM\Column(length: 255, unique: true)]
    private string $url;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'domain', targetEntity: LogOrigin::class)]
    private Collection $origins;

    public function __construct()
    {
        $this->origins = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = strtolower(rtrim(trim($url), '/'));

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

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
        return isset($this->url) ? $this->url : '';
    }
}

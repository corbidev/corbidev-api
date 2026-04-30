<?php

namespace App\Api\Logs\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_LEVEL')]
class LogLevel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'smallint')]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private string $name;

    #[ORM\Column(type: 'smallint', unique: true)]
    private int $levelInt;

    public function __construct(string $name, int $levelInt)
    {
        $this->name = strtoupper(trim($name));
        $this->levelInt = $levelInt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLevelInt(): int
    {
        return $this->levelInt;
    }
}

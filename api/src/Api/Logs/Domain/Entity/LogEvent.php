<?php

namespace App\Api\Logs\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_ENV')]
class LogEnv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'smallint')]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private string $name;

    public function __construct(string $name)
    {
        $this->name = strtolower(trim($name));
    }

    public function getName(): string
    {
        return $this->name;
    }
}
<?php

namespace App\Api\Logs\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'CBV_LOGS_ERROR_CODE')]
class LogErrorCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'smallint')]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $code;

    public function __construct(string $code)
    {
        $this->code = strtoupper(trim($code));
    }

    public function getCode(): string
    {
        return $this->code;
    }
}

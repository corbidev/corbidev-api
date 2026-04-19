<?php
namespace App\Api\Logs\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateLogEventDto
{
    #[Assert\NotBlank]
    public string $message;

    #[Assert\NotBlank]
    public string $level;

    #[Assert\NotBlank]
    public string $env;

    #[Assert\NotBlank]
    public array $origin;

    #[Assert\NotBlank]
    public string $fingerprint;

    public ?int $userId = null;
    public ?int $httpStatus = null;
    public ?string $errorCode = null;

    public ?\DateTimeImmutable $ts = null;

    public ?array $detail = null;
}

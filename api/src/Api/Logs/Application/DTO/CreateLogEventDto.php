<?php

namespace App\Api\Logs\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateLogEventDto
{
    #[Assert\NotBlank]
    public string $id;

    #[Assert\NotBlank]
    public string $message;

    #[Assert\NotBlank]
    public string $level;

    #[Assert\NotBlank]
    public string $env;

    #[Assert\NotBlank]
    public string $domain;

    public ?string $uri = null;
    public ?string $method = null;
    public ?string $ip = null;

    public ?string $client = null;
    public ?string $version = null;

    #[Assert\NotBlank]
    public string $fingerprint;

    public ?int $userId = null;
    public ?int $httpStatus = null;
    public ?string $errorCode = null;

    public ?array $context = null;
}

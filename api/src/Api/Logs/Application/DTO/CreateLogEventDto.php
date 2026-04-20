<?php

namespace App\Api\Logs\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/logs',
            host: '%api_host%',
            output: false // 🔥 important (pas de body)
        )
    ]
)]
final class CreateLogEventDto
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $externalId;

    #[Assert\NotBlank]
    #[Assert\Length(max: 1024)]
    public string $message;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['INFO','WARNING','ERROR','CRITICAL','ALERT','EMERGENCY'])]
    public string $level;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['dev','test','prod'])]
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

    #[Assert\Range(min: 100, max: 599)]
    public ?int $httpStatus = null;

    public ?string $errorCode = null;

    public ?array $context = null;
}

<?php

namespace App\Api\Logs\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Api\Logs\Application\Processor\LogProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/logs',
            host: '%api_host%',
            processor: LogProcessor::class,
            output: false
        )
    ]
)]
final class CreateLogEventDto
{
    #[Assert\NotBlank(message: 'externalId is required')]
    #[Assert\Uuid(message: 'externalId must be a valid UUID')]
    public string $externalId;

    #[Assert\NotBlank(message: 'message is required')]
    #[Assert\Length(
        max: 1024,
        maxMessage: 'message must not exceed 1024 characters'
    )]
    public string $message;

    #[Assert\NotBlank(message: 'level is required')]
    #[Assert\Choice(
        choices: ['INFO','WARNING','ERROR','CRITICAL','ALERT','EMERGENCY'],
        message: 'level must be one of: INFO, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY'
    )]
    public string $level;

    #[Assert\NotBlank(message: 'env is required')]
    #[Assert\Choice(
        choices: ['dev','test','prod'],
        message: 'env must be one of: dev, test, prod'
    )]
    public string $env;

    #[Assert\NotBlank(message: 'domain is required')]
    public string $domain;

    public ?string $uri = null;

    #[Assert\Choice(
        choices: ['GET','POST','PUT','PATCH','DELETE'],
        message: 'method must be a valid HTTP method'
    )]
    public ?string $method = null;

    #[Assert\Ip(message: 'ip must be a valid IP address')]
    public ?string $ip = null;

    public ?string $client = null;
    public ?string $version = null;

    #[Assert\NotBlank(message: 'fingerprint is required')]
    public string $fingerprint;

    public ?int $userId = null;

    #[Assert\Range(
        min: 100,
        max: 599,
        notInRangeMessage: 'httpStatus must be between 100 and 599'
    )]
    public ?int $httpStatus = null;

    public ?string $errorCode = null;

    public ?array $context = null;
}

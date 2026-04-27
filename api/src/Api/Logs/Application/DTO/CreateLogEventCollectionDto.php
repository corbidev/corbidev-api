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
final class CreateLogEventCollectionDto
{
    /**
     * @var CreateLogEventDto[]
     */
    #[Assert\NotBlank(message: 'logs cannot be empty')]
    #[Assert\Valid]
    public array $logs = [];
}
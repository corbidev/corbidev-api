<?php

namespace App\Api\Logs\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Logs\Application\DTO\CreateLogEventCollectionDto;
use App\Api\Logs\Application\DTO\CreateLogEventDto;
use App\Api\Logs\Application\Service\FileLogQueueService;

class LogProcessor implements ProcessorInterface
{
    public function __construct(
        private FileLogQueueService $queue
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // -------------------------
        // VALIDATION TYPE
        // -------------------------
        if (!$data instanceof CreateLogEventCollectionDto) {
            throw new \InvalidArgumentException('Invalid payload');
        }

        // -------------------------
        // TRANSFORMATION → ARRAY
        // -------------------------
        $payload = array_map(
            fn (CreateLogEventDto $dto) => $dto->toArray(),
            $data->logs
        );

        // -------------------------
        // QUEUE
        // -------------------------
        $this->queue->enqueue($payload);

        return null; // 204
    }
}

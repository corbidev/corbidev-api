<?php

namespace App\Api\Logs\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
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
        // NORMALISATION
        // -------------------------
        if (!is_array($data) || !isset($data[0])) {
            $data = [$data];
        }

        // -------------------------
        // VALIDATION TYPE
        // -------------------------
        foreach ($data as $item) {
            if (!$item instanceof CreateLogEventDto) {
                throw new \InvalidArgumentException('Invalid payload');
            }
        }

        // -------------------------
        // TRANSFORMATION → ARRAY
        // -------------------------
        $payload = array_map(
            fn (CreateLogEventDto $dto) => $dto->toArray(),
            $data
        );

        // -------------------------
        // QUEUE
        // -------------------------
        $this->queue->enqueue($payload);

        return null; // 204
    }
}

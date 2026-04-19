<?php
namespace App\Api\Logs\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Logs\Application\DTO\CreateLogEventDto;
use App\Api\Logs\Application\Handler\CreateLogHandler;

final class CreateLogProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateLogHandler $handler
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        \assert($data instanceof CreateLogEventDto);

        return $this->handler->handle($data);
    }
}

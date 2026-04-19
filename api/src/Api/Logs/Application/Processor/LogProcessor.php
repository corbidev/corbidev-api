<?php

namespace App\Api\Logs\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Logs\Application\Handler\CreateLogHandler;
use Doctrine\ORM\EntityManagerInterface;

class LogProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateLogHandler $handler,
        private EntityManagerInterface $em
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (is_array($data) && isset($data[0])) {
            // BULK
            foreach ($data as $item) {
                $this->handler->handle($item);
            }

            $this->em->flush();

            return ['status' => 'bulk ok'];
        }

        // SINGLE
        $this->handler->handle($data);

        $this->em->flush();

        return ['status' => 'ok'];
    }
}

<?php

namespace App\Api\Logs\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Api\Logs\Application\Handler\CreateLogHandler;
use App\Api\Logs\Application\DTO\CreateLogEventDto;
use Doctrine\ORM\EntityManagerInterface;

class LogProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateLogHandler $handler,
        private EntityManagerInterface $em
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // -------------------------
        // BULK (optionnel)
        // -------------------------
        if (is_array($data)) {

            foreach ($data as $item) {
                if (!$item instanceof CreateLogEventDto) {
                    throw new \InvalidArgumentException('Invalid payload in bulk');
                }

                $this->handler->handle($item);
            }

            // 🔥 flush unique (perf)
            $this->em->flush();

            return null; // 🔥 204
        }

        // -------------------------
        // SINGLE
        // -------------------------
        if (!$data instanceof CreateLogEventDto) {
            throw new \InvalidArgumentException('Invalid payload');
        }

        $this->handler->handle($data);

        // 🔥 obligatoire
        $this->em->flush();

        return null; // 🔥 204
    }
}

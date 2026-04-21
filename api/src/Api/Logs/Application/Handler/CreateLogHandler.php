<?php

namespace App\Api\Logs\Application\Handler;

use App\Api\Logs\Application\DTO\CreateLogEventDto;
use App\Api\Logs\Application\Factory\LogEventFactory;
use Doctrine\ORM\EntityManagerInterface;

final class CreateLogHandler
{
    public function __construct(
        private LogEventFactory $factory,
        private EntityManagerInterface $em
    ) {}

    public function handle(CreateLogEventDto $dto): void
    {
        // 🔥 création de l'entité via DTO (typé, fiable)
        $event = $this->factory->createFromDto($dto);

        // 🔥 indispensable pour Doctrine
        $this->em->persist($event);
    }
}

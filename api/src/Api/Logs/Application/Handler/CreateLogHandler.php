<?php

namespace App\Api\Logs\Application\Handler;

use App\Api\Logs\Application\Factory\LogEventFactory;
use Doctrine\ORM\EntityManagerInterface;

final class CreateLogHandler
{
    public function __construct(
        private LogEventFactory $factory,
        private EntityManagerInterface $em
    ) {}

    public function handle(array $data): void
    {
        $event = $this->factory->createFromArray($data);

        $this->em->persist($event);
    }
}

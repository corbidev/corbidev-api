<?php
namespace App\Api\Logs\Application\Handler;

use App\Api\Logs\Application\DTO\CreateLogEventDto;
use App\Api\Logs\Application\Factory\LogEventFactory;
use Doctrine\ORM\EntityManagerInterface;
use App\Api\Logs\Domain\Entity\LogEvent;

final class CreateLogHandler
{
    public function __construct(
        private LogEventFactory $factory,
        private EntityManagerInterface $em
    ) {}

    public function handle(CreateLogEventDto $dto): LogEvent
    {
        $event = $this->factory->createFromDto($dto);

        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }
}

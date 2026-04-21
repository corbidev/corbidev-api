<?php

namespace App\Api\Logs\Application\Handler;

use App\Api\Logs\Application\DTO\CreateLogEventDto;
use App\Api\Logs\Application\Factory\LogEventFactory;
use App\Shared\Domain\Error\DomainException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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

        $this->em->persist($event);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw DomainException::alreadyExists('Log already exists');
        } catch (\Throwable $e) {
            throw DomainException::database('Unable to persist log');
        }
    }
}

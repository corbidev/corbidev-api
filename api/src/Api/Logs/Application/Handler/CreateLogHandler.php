<?php

namespace App\Api\Logs\Application\Handler;

use App\Api\Logs\Application\DTO\CreateLogEventDto;
use App\Api\Logs\Application\Factory\LogEventFactory;
use App\Api\Logs\Domain\Service\LogValidator;
use App\Shared\Domain\Error\DomainException;
use Doctrine\ORM\EntityManagerInterface;

final class CreateLogHandler
{
    public function __construct(
        private readonly LogEventFactory $factory,
        private readonly EntityManagerInterface $em,
        private readonly LogValidator $validator
    ) {}

    public function handle(CreateLogEventDto $dto): void
    {
        // =========================
        // 🧠 Validation métier AVANT persistence
        // =========================
        $this->validator->validateUniqueExternalId($dto->externalId);

        // =========================
        // 🏗️ Création entité
        // =========================
        $event = $this->factory->createFromDto($dto);

        $this->em->persist($event);

        // =========================
        // 💾 Persistence
        // =========================
        try {
            $this->em->flush();
        } catch (\Throwable $e) {
            // ⚠️ fallback uniquement (DB / infra)
            throw DomainException::database('Unable to persist log');
        }
    }
}

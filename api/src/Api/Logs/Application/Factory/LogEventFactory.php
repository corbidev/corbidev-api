<?php

namespace App\Api\Logs\Application\Factory;

use App\Api\Logs\Domain\Entity\LogEvent;
use App\Api\Logs\Domain\Entity\LogLevel;
use App\Api\Logs\Domain\Entity\LogEnv;
use App\Api\Logs\Domain\Entity\LogErrorCode;

use App\Api\Logs\Application\DTO\CreateLogEventDto;

use App\Api\Logs\Infrastructure\Repository\LogLevelRepository;
use App\Api\Logs\Infrastructure\Repository\LogEnvRepository;
use App\Api\Logs\Infrastructure\Repository\LogErrorCodeRepository;

use Doctrine\ORM\EntityManagerInterface;

class LogEventFactory
{
    public function __construct(
        private EntityManagerInterface $em,
        private LogLevelRepository $levelRepo,
        private LogEnvRepository $envRepo,
        private LogErrorCodeRepository $errorCodeRepo,
    ) {}

    private array $levelCache = [];
    private array $envCache = [];
    private array $errorCodeCache = [];

    public function createFromDto(CreateLogEventDto $dto): LogEvent
    {
        $event = new LogEvent();

        // -------------------------
        // UUID CLIENT
        // -------------------------
        $event->setExternalId($dto->externalId);

        // -------------------------
        // LEVEL
        // -------------------------
        $levelKey = strtoupper($dto->level);

        $level = $this->levelCache[$levelKey]
            ??= $this->levelRepo->findOneByName($levelKey)
                ?? $this->createLevel($levelKey);

        $event->setLevel($level);

        // -------------------------
        // ENV
        // -------------------------
        $envKey = strtolower($dto->env);

        $env = $this->envCache[$envKey]
            ??= $this->envRepo->findOneByName($envKey)
                ?? $this->createEnv($envKey);

        $event->setEnv($env);

        // -------------------------
        // ERROR CODE
        // -------------------------
        if (!empty($dto->errorCode)) {
            $codeKey = strtoupper($dto->errorCode);

            $errorCode = $this->errorCodeCache[$codeKey]
                ??= $this->errorCodeRepo->findOneByCode($codeKey)
                    ?? $this->createErrorCode($codeKey);

            $event->setErrorCodeEntity($errorCode);
        }

        // -------------------------
        // DATA
        // -------------------------

        $event->setDomain($dto->domain);
        $event->setUri($dto->uri);
        $event->setMethod($dto->method);
        $event->setClient($dto->client);
        $event->setVersion($dto->version);

        $event->setMessage($dto->message);
        $event->setFingerprint($dto->fingerprint);

        $event->setUserId($dto->userId);
        $event->setHttpStatus($dto->httpStatus);

        if (!empty($dto->context)) {
            $event->setContext($dto->context);
        }

        return $event;
    }

    private function createLevel(string $name): LogLevel
    {
        $map = [
            'DEBUG' => 100,
            'INFO' => 200,
            'NOTICE' => 250,
            'WARNING' => 300,
            'ERROR' => 400,
            'CRITICAL' => 500,
            'ALERT' => 550,
            'EMERGENCY' => 600,
        ];

        $level = new LogLevel($name, $map[$name] ?? 200);
        $this->em->persist($level);

        return $level;
    }

    private function createEnv(string $name): LogEnv
    {
        $env = new LogEnv($name);
        $this->em->persist($env);

        return $env;
    }

    private function createErrorCode(string $code): LogErrorCode
    {
        $entity = new LogErrorCode($code);
        $this->em->persist($entity);

        return $entity;
    }

    public function reset(): void
    {
        $this->levelCache = [];
        $this->envCache = [];
        $this->errorCodeCache = [];
    }
}
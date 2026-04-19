<?php
namespace App\Api\Logs\Application\Factory;

use App\Api\Logs\Application\DTO\CreateLogEventDto;
use App\Api\Logs\Domain\Entity\LogEvent;
use App\Api\Logs\Domain\Entity\LogEnv;
use App\Api\Logs\Domain\Entity\LogLevel;
use App\Api\Logs\Domain\Entity\LogOrigin;
use App\Api\Logs\Infrastructure\Repository\LogLevelRepository;
use App\Api\Logs\Infrastructure\Repository\LogEnvRepository;
use App\Api\Logs\Infrastructure\Repository\LogOriginRepository;
use App\Api\Logs\Infrastructure\Repository\LogErrorCodeRepository;
use Doctrine\ORM\EntityManagerInterface;

final class LogEventFactory
{
    public function __construct(
        private LogLevelRepository $levelRepo,
        private LogEnvRepository $envRepo,
        private LogOriginRepository $originRepo,
        private LogErrorCodeRepository $errorCodeRepo,
        private EntityManagerInterface $em,
    ) {}

    public function createFromDto(CreateLogEventDto $dto): LogEvent
    {
        $level = $this->levelRepo->findOneBy(['name' => strtoupper($dto->level)]);
        if (!$level) {
            $level = new LogLevel($dto->level);
            $this->em->persist($level);
        }

        $env = $this->envRepo->findOneBy(['name' => strtolower($dto->env)]);
        if (!$env) {
            $env = new LogEnv($dto->env);
            $this->em->persist($env);
        }

        $origin = $this->originRepo->findOneBy([
            'domain' => $dto->origin['domain'],
            'client' => $dto->origin['client'],
            'version' => $dto->origin['version'],
        ]);

        if (!$origin) {
            $origin = new LogOrigin(
                $dto->origin['domain'],
                $dto->origin['client'],
                $dto->origin['version']
            );
            $this->em->persist($origin);
        }

        $errorCode = null;
        if ($dto->errorCode) {
            $errorCode = $this->errorCodeRepo->findOneBy(['code' => $dto->errorCode]);
        }

        $event = new LogEvent();

        $event->setMessage($dto->message);
        $event->setFingerprint($dto->fingerprint);
        $event->setTs($dto->ts ?? new \DateTimeImmutable());

        $event->setLevel($level);
        $event->setEnv($env);
        $event->setOrigin($origin);
        $event->setErrorCode($errorCode);

        $event->setUserId($dto->userId);
        $event->setHttpStatus($dto->httpStatus);

        return $event;
    }
}

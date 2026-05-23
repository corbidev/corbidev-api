<?php

namespace App\Api\Logs\Application\Handler;

use App\Api\Logs\Application\DTO\CreateLogEventDto;
use App\Api\Logs\Application\Factory\LogEventFactory;
use App\Api\Logs\Domain\Service\LogValidator;
use Doctrine\ORM\EntityManagerInterface;

final class CreateLogHandler
{
    private string $errorDir;

    public function __construct(
        private readonly LogEventFactory $factory,
        private readonly EntityManagerInterface $em,
        private readonly LogValidator $validator
    ) {
        // 📁 dossier erreurs (miroir de log_queue)
        $this->errorDir = dirname(__DIR__, 4) . '/var/log_queue_errors';
    }

    public function handle(CreateLogEventDto $dto, ?string $sourceFile = null): void
    {
        try {
            // =========================
            // 🔁 DUPLICATE → IGNORE
            // =========================
            if ($this->validator->existsExternalId($dto->externalId)) {
                return;
            }

            // =========================
            // 🏗️ Création entité
            // =========================
            $event = $this->factory->createFromDto($dto, $sourceFile);

            $this->em->persist($event);

        } catch (\Throwable $e) {

            // =========================
            // ❌ LOG ERREUR INTERNE
            // =========================
            $this->logError($dto, $e, $sourceFile);

            // 👉 on NE throw PAS
            // 👉 on protège le pipeline
        }
    }

    /**
     * Reset in-memory factory caches after EntityManager::clear().
     */
    public function reset(): void
    {
        $this->factory->reset();
    }

    // =========================
    // 🪵 ERROR LOGGER
    // =========================
    private function logError(
        CreateLogEventDto $dto,
        \Throwable $e,
        ?string $sourceFile
    ): void {
        try {
            $this->ensureDirectoryExists();

            $file = $this->resolveErrorFile($sourceFile);

            $payload = [
                'error' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                ],
                'log' => $dto->toArray(),
                'ts' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
                    ->format('Y-m-d\TH:i:s.u\Z'),
            ];

            file_put_contents(
                $file,
                json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL,
                FILE_APPEND
            );

        } catch (\Throwable) {
            // 🔒 ne jamais casser le flux pour un log d’erreur
        }
    }

    // =========================
    // 📁 NOM DU FICHIER ERREUR
    // =========================
    private function resolveErrorFile(?string $sourceFile): string
    {
        if (!$sourceFile) {
            return $this->errorDir . '/unknown.error.json';
        }

        $filename = basename($sourceFile);

        // 🔥 on garde le timestamp original
        return $this->errorDir . '/' . $filename . '.error.json';
    }

    // =========================
    // 📁 CREATE DIR
    // =========================
    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->errorDir)) {
            @mkdir($this->errorDir, 0775, true);
        }
    }
}

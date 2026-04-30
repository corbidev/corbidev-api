<?php

namespace App\Api\Logs\Infrastructure\Command;

use App\Api\Logs\Application\Service\FileLogQueueService;
use App\Api\Logs\Application\Handler\CreateLogHandler;
use App\Api\Logs\Application\DTO\CreateLogEventDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:process-log-queue')]
class ProcessLogQueueCommand extends Command
{
    public function __construct(
        private FileLogQueueService $queue,
        private CreateLogHandler $handler,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = microtime(true);

        $this->log($output, ['START', 'process-log-queue']);

        $files = $this->queue->listFiles();
        $totalFiles = count($files);

        if ($totalFiles === 0) {
            $this->log($output, ['INFO', 'No files']);
            return Command::SUCCESS;
        }

        $this->log($output, ['INFO', "Found $totalFiles files"]);

        $i = 0;
        $totalLogs = 0;
        $totalErrors = 0;

        foreach ($files as $file) {

            $processingFile = $this->queue->acquire($file);

            if (!$processingFile) {
                continue;
            }

            $hasError = false;

            try {
                $batch = $this->queue->read($processingFile);

                $this->log($output, ['PROCESS', $file, count($batch)]);

                foreach ($batch as $item) {

                    try {
                        $dto = $this->hydrateDto($item);

                        // ✅ handler idempotent (duplicate ignoré ici)
                        $this->handler->handle($dto, $processingFile);

                        $totalLogs++;
                        $i++;

                        if (($i % 100) === 0) {
                            $this->flush($output);
                        }

                    } catch (\Throwable $e) {
                        $hasError = true;
                        $totalErrors++;

                        $this->log($output, ['ERROR_LOG', $file, $e->getMessage()]);
                    }
                }

                // 🔥 flush final
                $this->flush($output);

                if ($hasError) {
                    $errorFile = $this->queue->markAsError($processingFile);
                    $this->log($output, ['ERROR_FILE', $errorFile]);
                } else {
                    $this->queue->delete($processingFile);
                    $this->log($output, ['OK', $file]);
                }

            } catch (\Throwable $e) {

                $totalErrors++;

                $errorFile = $this->queue->markAsError($processingFile);

                $this->log($output, ['ERROR_FATAL', $file, $e->getMessage()]);
                $this->log($output, ['ERROR_FILE', $errorFile]);
            }
        }

        $duration = round(microtime(true) - $start, 2);

        $this->log($output, [
            'DONE',
            "files=$totalFiles",
            "logs=$totalLogs",
            "errors=$totalErrors",
            "duration={$duration}s"
        ]);

        return Command::SUCCESS;
    }

    /**
     * 🔥 Flush simple (plus jamais bloqué)
     */
    private function flush(OutputInterface $output): void
    {
        $this->em->flush();
        $this->em->clear();
        $this->handler->reset();

        $this->log($output, ['FLUSH']);
    }

    private function hydrateDto(array $data): CreateLogEventDto
    {
        $dto = new CreateLogEventDto();

        $dto->externalId = $data['externalId'];
        $dto->message = $data['message'];
        $dto->level = $data['level'];
        $dto->env = $data['env'];
        $dto->domain = $data['domain'];

        $dto->uri = $data['uri'] ?? null;
        $dto->method = $data['method'] ?? null;
        $dto->ip = $data['ip'] ?? null;

        $dto->client = $data['client'] ?? null;
        $dto->version = $data['version'] ?? null;

        $dto->fingerprint = $data['fingerprint'];
        $dto->userId = $data['userId'] ?? null;

        $dto->httpStatus = $data['httpStatus'] ?? null;
        $dto->errorCode = $data['errorCode'] ?? null;

        $dto->context = $data['context'] ?? null;
        $dto->timestamp = $data['timestamp'] ?? null;

        // 🔥 request_id propagé si présent
        $dto->requestId = $data['requestId'] ?? null;

        return $dto;
    }

    private function log(OutputInterface $output, array $fields): void
    {
        $line = array_merge(
            [(new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')],
            $fields
        );

        $output->writeln(implode('|', $line));
    }
}

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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:process-log-retry')]
class ProcessLogRetryCommand extends Command
{
    private const MAX_AGE_SECONDS = 86400; // 24h

    public function __construct(
        private FileLogQueueService $queue,
        private CreateLogHandler $handler,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        #[Autowire('%env(MAIL_FROM)%')]
        private string $mailFrom,
        #[Autowire('%env(MAIL_TO)%')]
        private string $mailTo
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = microtime(true);

        $this->log($output, ['START', 'process-log-retry']);

        $files = glob($this->getQueueDir() . '/*.processing') ?: [];

        if (empty($files)) {
            $this->log($output, ['INFO', 'No processing files']);
            return Command::SUCCESS;
        }

        $this->log($output, ['INFO', 'Found', count($files), 'processing files']);

        $totalFiles = 0;
        $totalLogs = 0;
        $totalErrors = 0;
        $i = 0;

        foreach ($files as $file) {

            if (!is_file($file)) {
                continue;
            }

            // ⏱️ filtre 24h
            if (time() - filemtime($file) < self::MAX_AGE_SECONDS) {
                continue;
            }

            $totalFiles++;
            $hasError = false;

            $this->log($output, ['RETRY', $file]);

            try {
                $batch = $this->queue->read($file);

                if (empty($batch)) {
                    $this->log($output, ['EMPTY', $file]);
                    $this->queue->delete($file);
                    continue;
                }

                foreach ($batch as $item) {

                    try {
                        $dto = $this->hydrateDto($item);

                        // ✅ handler idempotent (duplicate ignoré)
                        $this->handler->handle($dto, $file);

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
                    $errorFile = $this->queue->markAsError($file);

                    $this->sendAlert($errorFile, 'Partial failure during retry');

                    $this->log($output, ['ERROR_FILE', $errorFile]);
                } else {
                    $this->queue->delete($file);
                    $this->log($output, ['OK', $file]);
                }

            } catch (\Throwable $e) {

                $totalErrors++;

                $errorFile = $this->queue->markAsError($file);

                $this->sendAlert($errorFile, $e->getMessage());

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
     * 🔥 Flush simple (jamais bloqué)
     */
    private function flush(OutputInterface $output): void
    {
        $this->em->flush();
        $this->em->clear();

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

        // 🔥 propagation request_id
        $dto->requestId = $data['requestId'] ?? null;

        return $dto;
    }

    private function sendAlert(string $file, string $error): void
    {
        $email = (new Email())
            ->from($this->mailFrom)
            ->to($this->mailTo)
            ->subject('Log queue ERROR')
            ->text("File: $file\nError: $error");

        $this->mailer->send($email);
    }

    private function log(OutputInterface $output, array $fields): void
    {
        $line = array_merge(
            [(new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')],
            $fields
        );

        $output->writeln(implode('|', $line));
    }

    private function getQueueDir(): string
    {
        return __DIR__ . '/../../../../var/log_queue';
    }
}
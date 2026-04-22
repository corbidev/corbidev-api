<?php

namespace App\Api\Logs\Infrastructure\Command;

use App\Api\Logs\Application\Service\FileLogQueueService;
use App\Api\Logs\Application\Handler\CreateLogHandler;
use App\Api\Logs\Application\DTO\CreateLogEventDto;
use App\Api\Logs\Application\Factory\LogEventFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:process-log-retry')]
class ProcessLogRetryCommand extends Command
{
    public function __construct(
        private FileLogQueueService $queue,
        private CreateLogHandler $handler,
        private EntityManagerInterface $em,
        private LogEventFactory $factory,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = glob($this->queueDir() . '/*.processing') ?: [];

        if (empty($files)) {
            $output->writeln('<info>No processing files to retry</info>');
            return Command::SUCCESS;
        }

        foreach ($files as $file) {

            // ⏱️ filtre âge (> 1 heure ici)
            if (time() - filemtime($file) < 3600) {
                continue;
            }

            $output->writeln("Retry: $file");

            try {
                $content = file_get_contents($file);
                $batch = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                $i = 0;

                foreach ($batch as $item) {
                    $dto = $this->hydrateDto($item);
                    $this->handler->handle($dto);

                    if (($i % 100) === 0) {
                        $this->em->flush();
                        $this->em->clear();
                        $this->factory->reset();
                    }

                    $i++;
                }

                $this->em->flush();

                // ✅ succès → suppression
                unlink($file);

            } catch (\Throwable $e) {

                // ❌ échec → rename failed
                $failedFile = str_replace('.processing', '.failed', $file);
                rename($file, $failedFile);

                // 📧 mail
                $this->sendAlert($failedFile, $e->getMessage());

                $output->writeln("<error>Failed: $failedFile</error>");
            }
        }

        return Command::SUCCESS;
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

        return $dto;
    }

    private function sendAlert(string $file, string $error): void
    {
        $email = (new Email())
            ->from('noreply@yourdomain.com')
            ->to('admin@yourdomain.com')
            ->subject('Log queue FAILED')
            ->text("File: $file\nError: $error");

        $this->mailer->send($email);
    }

    private function queueDir(): string
    {
        // 👉 adapte si besoin (ou injecte param Symfony)
        return __DIR__ . '/../../../../var/log_queue';
    }
}
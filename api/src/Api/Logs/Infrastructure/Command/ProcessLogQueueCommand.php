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

#[AsCommand(name: 'app:process-log-queue')]
class ProcessLogQueueCommand extends Command
{
    public function __construct(
        private FileLogQueueService $queue,
        private CreateLogHandler $handler,
        private EntityManagerInterface $em,
        private LogEventFactory $factory
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->queue->listFiles();

        if (empty($files)) {
            $output->writeln('<info>No files to process</info>');
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('<info>%d file(s) to process</info>', count($files)));

        $i = 0;
        $processed = 0;

        foreach ($files as $file) {

            try {
                $batch = $this->queue->readAndDelete($file);

                foreach ($batch as $item) {

                    // 🔁 reconstruction DTO
                    $dto = $this->hydrateDto($item);

                    // 🧠 traitement métier
                    $this->handler->handle($dto);

                    $processed++;
                    $i++;

                    // 🔥 batch flush
                    if (($i % 100) === 0) {
                        $this->em->flush();
                        $this->em->clear();
                        $this->factory->reset();

                        $output->writeln("Flushed 100 logs...");
                    }
                }

            } catch (\Throwable $e) {
                // ⚠️ on log et on continue
                $output->writeln(sprintf(
                    '<error>Error processing file %s : %s</error>',
                    $file,
                    $e->getMessage()
                ));
            }
        }

        // 🔥 flush final
        $this->em->flush();

        $output->writeln(sprintf(
            '<info>Done. %d logs processed.</info>',
            $processed
        ));

        return Command::SUCCESS;
    }

    /**
     * 🔁 Array → DTO
     */
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
}
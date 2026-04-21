<?php

namespace App\Command;

use App\Api\Logs\Application\Service\FileLogQueueService;
use App\Api\Logs\Application\Handler\CreateLogHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeLogsCommand extends Command
{
    protected static $defaultName = 'app:logs:consume';

    public function __construct(
        private FileLogQueueService $queue,
        private CreateLogHandler $handler,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = $this->queue->listFiles();

        if (empty($files)) {
            $output->writeln('<info>No log files to process</info>');
            return Command::SUCCESS;
        }

        $totalProcessed = 0;

        foreach ($files as $file) {
            $output->writeln("<comment>Processing file: $file</comment>");

            $count = 0;

            $this->queue->consumeFile($file, function ($log) use (&$count, $output) {

                try {
                    $this->handler->handle($log);
                    $count++;

                    // 🔥 flush batch tous les 100
                    if ($count % 100 === 0) {
                        $this->em->flush();
                        $this->em->clear();
                    }

                } catch (\Throwable $e) {
                    // ⚠️ ne bloque pas tout
                    $output->writeln('<error>Log skipped: ' . $e->getMessage() . '</error>');
                }
            });

            // flush final du fichier
            $this->em->flush();
            $this->em->clear();

            $output->writeln("<info>File processed: $count logs</info>");

            $totalProcessed += $count;
        }

        $output->writeln("<info>Total logs processed: $totalProcessed</info>");

        return Command::SUCCESS;
    }
}
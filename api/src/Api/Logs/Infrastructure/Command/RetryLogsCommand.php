<?php

namespace App\Command;

use App\Api\Logs\Application\Handler\CreateLogHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetryLogsCommand extends Command
{
    protected static $defaultName = 'app:logs:retry';

    private const MAX_RETRY = 5;

    public function __construct(
        private string $logDir,
        private CreateLogHandler $handler,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = glob($this->logDir . '/logs_*.lock.*') ?: [];

        if (empty($files)) {
            $output->writeln('<info>No failed files</info>');
            return Command::SUCCESS;
        }

        $now = time();
        $total = 0;

        foreach ($files as $file) {

            // ⏱ skip < 24h
            if (filemtime($file) > $now - 86400) {
                continue;
            }

            // 🔢 détecter retry
            $retry = 0;
            if (preg_match('/\.retry(\d+)$/', $file, $match)) {
                $retry = (int)$match[1];
            }

            if ($retry >= self::MAX_RETRY) {
                $output->writeln("<error>Max retry reached: $file</error>");
                continue;
            }

            $output->writeln("<comment>Retrying: $file</comment>");

            $count = 0;

            try {
                $handle = fopen($file, 'r');

                if (!$handle) {
                    throw new \RuntimeException('Cannot open file');
                }

                while (($line = fgets($handle)) !== false) {
                    $data = json_decode($line, true);

                    if (!$data) {
                        continue;
                    }

                    try {
                        $this->handler->handle($data);
                        $count++;

                        if ($count % 100 === 0) {
                            $this->em->flush();
                            $this->em->clear();
                        }

                    } catch (\Throwable $e) {
                        $output->writeln('<error>Skipped: ' . $e->getMessage() . '</error>');
                    }
                }

                fclose($handle);

                $this->em->flush();
                $this->em->clear();

                // ✅ succès → suppression
                unlink($file);

                $output->writeln("<info>Recovered: $count logs</info>");
                $total += $count;

            } catch (\Throwable $e) {

                // 🔥 incrément retry
                $newRetry = $retry + 1;

                $newFile = preg_replace(
                    '/(\.retry\d+)?$/',
                    ".retry{$newRetry}",
                    $file
                );

                rename($file, $newFile);

                $output->writeln("<error>Retry failed → $newFile</error>");
            }
        }

        $output->writeln("<info>Total recovered: $total</info>");

        return Command::SUCCESS;
    }
}
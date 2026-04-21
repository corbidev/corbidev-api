<?php

namespace App\Api\Logs\Application\Service;

class FileLogQueueService
{
    public function __construct(
        private string $dir
    ) {}

    public function push(array $logs): void
    {
        $file = $this->getCurrentFile();

        $lines = '';

        foreach ($logs as $log) {
            $lines .= json_encode($log, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }

        file_put_contents($file, $lines, FILE_APPEND | LOCK_EX);
    }

    public function listFiles(): array
    {
        return glob($this->dir . '/logs_*.log') ?: [];
    }

    public function consumeFile(string $file, callable $callback): void
    {
        $handle = fopen($file, 'r');

        if (!$handle) {
            return;
        }

        while (($line = fgets($handle)) !== false) {
            $data = json_decode($line, true);

            if ($data) {
                $callback($data);
            }
        }

        fclose($handle);

        unlink($file);
    }

    private function getCurrentFile(): string
    {
        $date = date('Ymd_Hi');

        return sprintf('%s/logs_%s.log', $this->dir, $date);
    }
}
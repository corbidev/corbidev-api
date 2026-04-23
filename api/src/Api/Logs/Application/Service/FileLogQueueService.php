<?php

namespace App\Api\Logs\Application\Service;

class FileLogQueueService
{
    public function __construct(
        private string $dir
    ) {}

    // =========================
    // 📥 ENQUEUE (1 fichier = 1 batch)
    // =========================
    public function enqueue(array $logs): void
    {
        $this->ensureDirectoryExists();

        $file = $this->generateFilename();

        $json = json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (file_put_contents($file, $json) === false) {
            throw new \RuntimeException("Unable to write queue file: $file");
        }
    }

    // =========================
    // 📂 LIST FILES
    // =========================
    public function listFiles(): array
    {
        $this->ensureDirectoryExists();

        $files = glob($this->dir . '/queue_*.log') ?: [];

        sort($files); // ordre chronologique

        return $files;
    }

    // =========================
    // 🔒 ACQUIRE FILE (.processing)
    // =========================
    public function acquire(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }

        $processingFile = $file . '.processing';

        if (!@rename($file, $processingFile)) {
            return null; // déjà pris par un autre process
        }

        return $processingFile;
    }

    // =========================
    // 📖 READ FILE
    // =========================
    public function read(string $processingFile): array
    {
        if (!is_file($processingFile)) {
            throw new \RuntimeException("File not found: $processingFile");
        }

        $content = file_get_contents($processingFile);

        if ($content === false) {
            throw new \RuntimeException("Unable to read file: $processingFile");
        }

        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Invalid JSON in file: $processingFile");
        }
    }

    // =========================
    // ✅ DELETE (SUCCESS)
    // =========================
    public function delete(string $processingFile): void
    {
        if (is_file($processingFile)) {
            @unlink($processingFile);
        }
    }

    // =========================
    // ❌ MARK AS ERROR
    // =========================
    public function markAsError(string $processingFile): string
    {
        if (!is_file($processingFile)) {
            return $processingFile;
        }

        $errorFile = preg_replace('/\.processing$/', '.error', $processingFile);

        if (!$errorFile) {
            $errorFile = $processingFile . '.error';
        }

        @rename($processingFile, $errorFile);

        return $errorFile;
    }

    // =========================
    // 🏷️ NOM UNIQUE
    // =========================
    private function generateFilename(): string
    {
        $date = (new \DateTime())->format('Y-m-d-His-u');

        return sprintf('%s/queue_%s.log', rtrim($this->dir, '/'), $date);
    }

    // =========================
    // 📁 ENSURE DIRECTORY
    // =========================
    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->dir)) {
            if (!@mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
                throw new \RuntimeException("Unable to create directory: {$this->dir}");
            }
        }
    }
}
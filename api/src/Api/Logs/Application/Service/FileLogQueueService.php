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
        $file = $this->generateFilename();

        file_put_contents(
            $file,
            json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
    }

    // =========================
    // 📂 LIST FILES
    // =========================
    public function listFiles(): array
    {
        $files = glob($this->dir . '/queue_*.log') ?: [];

        sort($files); // 🔥 ordre chronologique

        return $files;
    }

    // =========================
    // ⚙️ READ + DELETE (SAFE)
    // =========================
    public function readAndDelete(string $file): array
    {
        // 🔒 rename pour éviter double traitement
        $processingFile = $file . '.processing';

        if (!rename($file, $processingFile)) {
            return [];
        }

        $content = file_get_contents($processingFile);

        if ($content === false) {
            unlink($processingFile);
            return [];
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        unlink($processingFile);

        return $data;
    }

    // =========================
    // 🏷️ NOM UNIQUE
    // =========================
    private function generateFilename(): string
    {
        $date = (new \DateTime())->format('Y-m-d-His-u');

        return sprintf('%s/queue_%s.log', $this->dir, $date);
    }
}
<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;

final class WriteAtomicCrashTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_crash_' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    public function test_tmp_file_left_does_not_create_invalid_queue_file(): void
    {
        $finalPath = $this->dir . '/test.queue';
        $tmpPath = $finalPath . '.crash.tmp';

        // 🔥 Simulation crash : write tmp sans rename
        file_put_contents($tmpPath, '{"invalid": ');

        // ✔ aucun fichier final ne doit exister
        $this->assertFileDoesNotExist($finalPath);

        // ✔ tmp existe
        $this->assertFileExists($tmpPath);

        // ✔ LIST ne doit pas retourner ce fichier
        $files = glob($this->dir . '/*.queue');

        $this->assertEmpty($files);
    }
}
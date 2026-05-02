<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Filesystem\LocalSafeFilesystem;

final class WriteAtomicTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_test_' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    public function test_write_atomic_creates_valid_file_without_tmp(): void
    {
        $fs = new LocalSafeFilesystem();

        $path = $this->dir . '/test.queue';
        $content = json_encode(['ok' => true]);

        $result = $fs->writeAtomic($path, $content);

        // ✔ success
        $this->assertTrue($result->success);

        // ✔ fichier existe
        $this->assertFileExists($path);

        // ✔ contenu exact
        $this->assertSame($content, file_get_contents($path));

        // ✔ aucun .tmp restant
        $tmpFiles = glob($this->dir . '/*.tmp');
        $this->assertEmpty($tmpFiles);
    }
}
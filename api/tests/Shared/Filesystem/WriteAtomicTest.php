<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\LocalSafeFilesystem;
use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\PhpErrorLoggerInterface;

final class WriteAtomicTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_test_' . uniqid('', true);
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->dir)) {
            return;
        }

        foreach (glob($this->dir . '/*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($this->dir);
    }

    private function createFilesystem(): LocalSafeFilesystem
    {
        return new LocalSafeFilesystem(
            new EmergencyLogger(
                new class implements PhpErrorLoggerInterface {
                    public function log(string $message): void {}
                }
            )
        );
    }

    public function test_write_atomic_creates_valid_file_without_tmp(): void
    {
        $fs = $this->createFilesystem();

        $path = $this->dir . '/test.queue';
        $content = json_encode(['ok' => true]);

        $result = $fs->writeAtomic($path, $content);

        // ✔ success
        $this->assertTrue($result->isSuccess());

        // ✔ fichier existe
        $this->assertFileExists($path);

        // ✔ contenu exact
        $this->assertSame($content, file_get_contents($path));

        // ✔ aucun .tmp restant
        $tmpFiles = glob($this->dir . '/*.tmp');
        $this->assertEmpty($tmpFiles);
    }
}
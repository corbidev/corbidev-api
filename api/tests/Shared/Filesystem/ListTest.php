<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\LocalSafeFilesystem;
use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\PhpErrorLoggerInterface;

/**
 * Tests du job LIST du SafeFilesystem.
 *
 * Objectif :
 * Vérifier que la méthode list() retourne correctement
 * les fichiers selon un pattern donné.
 *
 * Pourquoi :
 * Le filtrage métier (ex: .queue) ne doit PAS être fait ici.
 */
final class ListTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_list_' . uniqid('', true);
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

        rmdir($this->dir);
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

    public function test_returns_files_matching_pattern(): void
    {
        $fs = $this->createFilesystem();

        file_put_contents($this->dir . '/a.queue', 'ok');
        file_put_contents($this->dir . '/b.queue', 'ok');
        file_put_contents($this->dir . '/c.tmp', 'tmp');

        $files = $fs->list($this->dir, '*.queue');

        $this->assertCount(2, $files);

        foreach ($files as $file) {
            $this->assertStringEndsWith('.queue', $file);
        }
    }

    public function test_ignores_non_matching_pattern(): void
    {
        $fs = $this->createFilesystem();

        file_put_contents($this->dir . '/a.queue.tmp', 'tmp');
        file_put_contents($this->dir . '/b.tmp', 'tmp');

        $files = $fs->list($this->dir, '*.queue');

        $this->assertEmpty($files);
    }

    public function test_returns_empty_array_if_directory_is_empty(): void
    {
        $fs = $this->createFilesystem();

        $files = $fs->list($this->dir);

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    public function test_returns_empty_array_if_directory_does_not_exist(): void
    {
        $fs = $this->createFilesystem();

        $files = $fs->list('/path/does/not/exist');

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    public function test_returns_full_paths(): void
    {
        $fs = $this->createFilesystem();

        $file = $this->dir . '/a.queue';
        file_put_contents($file, 'ok');

        $files = $fs->list($this->dir);

        $this->assertSame([$file], $files);
    }

    public function test_returns_sorted_files(): void
    {
        $fs = $this->createFilesystem();

        file_put_contents($this->dir . '/b.queue', 'ok');
        file_put_contents($this->dir . '/a.queue', 'ok');

        $files = $fs->list($this->dir);

        $this->assertSame([
            $this->dir . '/a.queue',
            $this->dir . '/b.queue',
        ], $files);
    }
}
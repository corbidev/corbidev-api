<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Filesystem\LocalSafeFilesystem;

/**
 * Tests du job LIST du SafeFilesystem.
 *
 * Objectif :
 * Garantir que seuls les fichiers exploitables (.queue) sont retournés,
 * sans jamais exposer de fichiers temporaires ou invalides.
 *
 * Pourquoi :
 * LIST est une brique critique pour le système de queue.
 * Une mauvaise implémentation peut entraîner :
 * - lecture de fichiers corrompus
 * - race conditions
 * - comportements non déterministes
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
                unlink($file);
            }
        }

        rmdir($this->dir);
    }

    public function test_returns_only_queue_files(): void
    {
        $fs = new LocalSafeFilesystem();

        file_put_contents($this->dir . '/a.queue', 'ok');
        file_put_contents($this->dir . '/b.queue', 'ok');
        file_put_contents($this->dir . '/c.tmp', 'tmp');
        file_put_contents($this->dir . '/d.txt', 'txt');

        $files = $fs->list($this->dir);

        $this->assertCount(2, $files);

        foreach ($files as $file) {
            $this->assertStringEndsWith('.queue', $file);
        }
    }

    public function test_ignores_tmp_files(): void
    {
        $fs = new LocalSafeFilesystem();

        file_put_contents($this->dir . '/a.queue.tmp', 'tmp');
        file_put_contents($this->dir . '/b.tmp', 'tmp');

        $files = $fs->list($this->dir);

        $this->assertEmpty($files);
    }

    public function test_returns_empty_array_if_directory_is_empty(): void
    {
        $fs = new LocalSafeFilesystem();

        $files = $fs->list($this->dir);

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    public function test_returns_empty_array_if_directory_does_not_exist(): void
    {
        $fs = new LocalSafeFilesystem();

        $files = $fs->list('/path/does/not/exist');

        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    public function test_returns_full_paths(): void
    {
        $fs = new LocalSafeFilesystem();

        $file = $this->dir . '/a.queue';
        file_put_contents($file, 'ok');

        $files = $fs->list($this->dir);

        $this->assertSame([$file], $files);
    }

    public function test_returns_sorted_files(): void
    {
        $fs = new LocalSafeFilesystem();

        file_put_contents($this->dir . '/b.queue', 'ok');
        file_put_contents($this->dir . '/a.queue', 'ok');

        $files = $fs->list($this->dir);

        $this->assertSame([
            $this->dir . '/a.queue',
            $this->dir . '/b.queue',
        ], $files);
    }
}
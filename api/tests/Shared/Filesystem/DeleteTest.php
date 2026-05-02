<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Filesystem\LocalSafeFilesystem;

/**
 * Tests du job DELETE du SafeFilesystem.
 *
 * Objectif :
 * Garantir une suppression sûre, idempotente et sans effet de bord.
 *
 * Pourquoi :
 * DELETE intervient en fin de traitement queue.
 * Une erreur ici peut laisser des fichiers zombies ou casser le flux.
 */
final class DeleteTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_delete_' . uniqid('', true);
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

    public function test_delete_existing_file(): void
    {
        $fs = new LocalSafeFilesystem();

        $file = $this->dir . '/file.queue';
        file_put_contents($file, 'data');

        $result = $fs->delete($file);

        $this->assertTrue($result->success);
        $this->assertFileDoesNotExist($file);
    }

    public function test_delete_is_idempotent(): void
    {
        $fs = new LocalSafeFilesystem();

        $file = $this->dir . '/missing.queue';

        $result = $fs->delete($file);

        $this->assertTrue($result->success);
        $this->assertFileDoesNotExist($file);
    }

    public function test_delete_fails_on_directory(): void
    {
        $fs = new LocalSafeFilesystem();

        $dir = $this->dir . '/subdir';
        mkdir($dir);

        $result = $fs->delete($dir);

        $this->assertFalse($result->success);

        $this->assertDirectoryExists($dir);
    }

    public function test_delete_handles_permission_error(): void
    {
        $fs = new LocalSafeFilesystem();

        $file = $this->dir . '/protected.queue';
        file_put_contents($file, 'data');

        // rendre le fichier non supprimable (simulation simple)
        chmod($file, 0444);

        $result = $fs->delete($file);

        // selon OS, peut réussir ou échouer → on vérifie robustesse
        if ($result->success) {
            $this->assertFileDoesNotExist($file);
        } else {
            $this->assertFileExists($file);
        }
    }

    public function test_delete_never_throws_exception(): void
    {
        $fs = new LocalSafeFilesystem();

        $result = $fs->delete('/invalid/path/file.queue');

        $this->assertInstanceOf(
            \Shared\Infrastructure\Filesystem\FilesystemResult::class,
            $result
        );
    }
}
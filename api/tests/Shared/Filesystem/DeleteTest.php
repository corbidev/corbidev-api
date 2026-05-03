<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\LocalSafeFilesystem;
use App\Shared\Infrastructure\Filesystem\FilesystemResult;
use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\PhpErrorLoggerInterface;

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
        $this->removeDirectory($this->dir);
    }

    /**
     * Suppression récursive robuste du dossier de test.
     *
     * Pourquoi :
     * Éviter tout état résiduel (fichiers ou dossiers)
     * pouvant casser les tests suivants.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
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

    public function test_delete_existing_file(): void
    {
        $fs = $this->createFilesystem();

        $file = $this->dir . '/file.queue';
        file_put_contents($file, 'data');

        $result = $fs->delete($file);

        $this->assertTrue($result->isSuccess());
        $this->assertFileDoesNotExist($file);
    }

    public function test_delete_is_idempotent(): void
    {
        $fs = $this->createFilesystem();

        $file = $this->dir . '/missing.queue';

        $result = $fs->delete($file);

        $this->assertTrue($result->isSuccess());
        $this->assertFileDoesNotExist($file);
    }

    public function test_delete_fails_on_directory(): void
    {
        $fs = $this->createFilesystem();

        $dir = $this->dir . '/subdir';
        mkdir($dir);

        $result = $fs->delete($dir);

        $this->assertTrue($result->isFailure());
        $this->assertDirectoryExists($dir);
    }

    public function test_delete_handles_permission_error(): void
    {
        $fs = $this->createFilesystem();

        $file = $this->dir . '/protected.queue';
        file_put_contents($file, 'data');

        chmod($file, 0444);

        $result = $fs->delete($file);

        if ($result->isSuccess()) {
            $this->assertFileDoesNotExist($file);
        } else {
            $this->assertFileExists($file);
        }
    }

    public function test_delete_never_throws_exception(): void
    {
        $fs = $this->createFilesystem();

        $result = $fs->delete('/invalid/path/file.queue');

        $this->assertInstanceOf(FilesystemResult::class, $result);
    }
}
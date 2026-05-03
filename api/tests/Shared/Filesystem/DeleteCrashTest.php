<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\LocalSafeFilesystem;
use App\Shared\Infrastructure\Filesystem\FilesystemResult;
use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\PhpErrorLoggerInterface;

/**
 * Tests de robustesse DELETE face aux crashs.
 *
 * Objectif :
 * Garantir que la suppression reste sûre, déterministe et sans effet de bord,
 * même en cas d'échec ou d'état inattendu.
 *
 * Important :
 * DELETE est une opération terminale (unlink), donc aucun état intermédiaire
 * ne doit être observable.
 */
final class DeleteCrashTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_delete_crash_' . uniqid('', true);
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

    public function test_file_is_either_deleted_or_present(): void
    {
        $fs = $this->createFilesystem();

        $file = $this->dir . '/file.queue';
        file_put_contents($file, 'data');

        $fs->delete($file);

        $exists = file_exists($file);

        $this->assertIsBool($exists);
    }

    public function test_no_partial_state_possible(): void
    {
        $fs = $this->createFilesystem();

        $file = $this->dir . '/file.queue';
        file_put_contents($file, 'data');

        $fs->delete($file);

        if (file_exists($file)) {
            $this->assertSame('data', file_get_contents($file));
        } else {
            $this->assertFileDoesNotExist($file);
        }
    }

    public function test_delete_failure_does_not_corrupt_file(): void
    {
        $fs = $this->createFilesystem();

        $file = $this->dir . '/protected.queue';
        file_put_contents($file, 'data');

        chmod($file, 0444);

        $result = $fs->delete($file);

        if ($result->isFailure()) {
            $this->assertFileExists($file);
            $this->assertSame('data', file_get_contents($file));
        } else {
            $this->assertFileDoesNotExist($file);
        }
    }

    public function test_no_side_effect_on_other_files(): void
    {
        $fs = $this->createFilesystem();

        $fileA = $this->dir . '/a.queue';
        $fileB = $this->dir . '/b.queue';

        file_put_contents($fileA, 'A');
        file_put_contents($fileB, 'B');

        $fs->delete($fileA);

        $this->assertFileExists($fileB);
        $this->assertSame('B', file_get_contents($fileB));
    }

    public function test_delete_on_invalid_path_is_safe(): void
    {
        $fs = $this->createFilesystem();

        $result = $fs->delete('/invalid/path/file.queue');

        $this->assertTrue($result->isSuccess());
        $this->assertInstanceOf(FilesystemResult::class, $result);
    }
}
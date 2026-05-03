<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\FileLineEditor;
use App\Shared\Infrastructure\Filesystem\FilesystemInterface;
use App\Shared\Infrastructure\Filesystem\FilesystemResult;

/**
 * Tests de résilience du FileLineEditor.
 *
 * Objectif :
 * Vérifier que le composant ne casse jamais
 * ET produit un comportement cohérent même en cas d'erreur.
 */
final class FileLineEditorCrashTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/file_editor_crash_' . uniqid('', true);
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($this->dir);
    }

    public function test_it_survives_filesystem_failure(): void
    {
        $filesystem = $this->createMock(FilesystemInterface::class);

        $filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->willReturn(FilesystemResult::failure('disk full'));

        $editor = new FileLineEditor($filesystem);

        $file = $this->dir . '/file.txt';
        file_put_contents($file, "line1\n");

        $result = $editor->append($file, 'new');

        // ✔ on teste un vrai comportement
        $this->assertTrue($result->isFailure());

        // ✔ fichier original intact (pas de corruption)
        $this->assertSame("line1\n", file_get_contents($file));
    }

    public function test_it_handles_corrupted_file_content(): void
    {
        $filesystem = $this->createMock(FilesystemInterface::class);

        $filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->with($this->anything(), $this->stringContains('safe'))
            ->willReturn(FilesystemResult::success());

        $editor = new FileLineEditor($filesystem);

        $file = $this->dir . '/file.txt';
        file_put_contents($file, "\x00\xFF\x10INVALID\n");

        $result = $editor->append($file, 'safe');

        $this->assertTrue($result->isSuccess());
    }

    public function test_it_handles_large_file(): void
    {
        $filesystem = $this->createMock(FilesystemInterface::class);

        $filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->willReturn(FilesystemResult::success());

        $editor = new FileLineEditor($filesystem);

        $file = $this->dir . '/file.txt';
        $largeContent = str_repeat("line\n", 100000);

        file_put_contents($file, $largeContent);

        $result = $editor->append($file, 'end');

        $this->assertTrue($result->isSuccess());
    }

    public function test_it_survives_modifier_exception(): void
    {
        $filesystem = $this->createMock(FilesystemInterface::class);

        $filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->with(
                $this->anything(),
                $this->stringContains("line2") // ✔ ligne conservée
            )
            ->willReturn(FilesystemResult::success());

        $editor = new FileLineEditor($filesystem);

        $file = $this->dir . '/file.txt';
        file_put_contents($file, "line1\nline2\n");

        $result = $editor->replace($file, function (string $line) {
            if ($line === 'line2') {
                throw new \RuntimeException('boom');
            }
            return strtoupper($line);
        });

        $this->assertTrue($result->isSuccess());
    }

    public function test_it_survives_filter_exception(): void
    {
        $filesystem = $this->createMock(FilesystemInterface::class);

        $filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->with(
                $this->anything(),
                $this->stringContains("b") // ✔ ligne conservée
            )
            ->willReturn(FilesystemResult::success());

        $editor = new FileLineEditor($filesystem);

        $file = $this->dir . '/file.txt';
        file_put_contents($file, "a\nb\nc\n");

        $result = $editor->delete($file, function (string $line) {
            if ($line === 'b') {
                throw new \RuntimeException('fail');
            }
            return false;
        });

        $this->assertTrue($result->isSuccess());
    }

    public function test_it_survives_read_failure(): void
    {
        $filesystem = $this->createMock(FilesystemInterface::class);

        $filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->with($this->anything(), "new\n")
            ->willReturn(FilesystemResult::success());

        $editor = new FileLineEditor($filesystem);

        $file = $this->dir . '/file.txt';
        touch($file);
        chmod($file, 0000);

        $result = $editor->append($file, 'new');

        chmod($file, 0644);

        // ✔ comportement attendu : fallback → append seul
        $this->assertTrue($result->isSuccess());
    }
}
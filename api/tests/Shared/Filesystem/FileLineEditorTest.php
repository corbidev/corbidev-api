<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\FileLineEditor;
use App\Shared\Infrastructure\Filesystem\FilesystemInterface;
use App\Shared\Infrastructure\Filesystem\FilesystemResult;

/**
 * Tests du FileLineEditor.
 */
final class FileLineEditorTest extends TestCase
{
    private FilesystemInterface $filesystem;
    private string $dir;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(FilesystemInterface::class);

        $this->dir = sys_get_temp_dir() . '/file_editor_' . uniqid('', true);
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

    public function test_append_adds_line_at_end(): void
    {
        $editor = new FileLineEditor($this->filesystem);

        $file = $this->dir . '/file.txt';
        $expectedContent = "line1\nline2\nnew\n";

        $this->filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->with($file, $expectedContent)
            ->willReturn(FilesystemResult::success());

        file_put_contents($file, "line1\nline2\n");

        $editor->append($file, 'new');
    }

    public function test_prepend_adds_line_at_start(): void
    {
        $editor = new FileLineEditor($this->filesystem);

        $file = $this->dir . '/file.txt';
        $expectedContent = "new\nline1\nline2\n";

        $this->filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->with($file, $expectedContent)
            ->willReturn(FilesystemResult::success());

        file_put_contents($file, "line1\nline2\n");

        $editor->prepend($file, 'new');
    }

    public function test_replace_transforms_lines(): void
    {
        $editor = new FileLineEditor($this->filesystem);

        $file = $this->dir . '/file.txt';
        $expectedContent = "LINE1\nLINE2\n";

        $this->filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->with($file, $expectedContent)
            ->willReturn(FilesystemResult::success());

        file_put_contents($file, "line1\nline2\n");

        $editor->replace($file, fn(string $line) => strtoupper($line));
    }

    public function test_delete_removes_matching_lines(): void
    {
        $editor = new FileLineEditor($this->filesystem);

        $file = $this->dir . '/file.txt';
        $expectedContent = "keep\n";

        $this->filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->with($file, $expectedContent)
            ->willReturn(FilesystemResult::success());

        file_put_contents($file, "keep\nremove\n");

        $editor->delete($file, fn(string $line) => $line === 'remove');
    }

    public function test_operations_on_missing_file(): void
    {
        $editor = new FileLineEditor($this->filesystem);

        $file = $this->dir . '/missing.txt';
        $expectedContent = "new\n";

        $this->filesystem
            ->expects($this->once())
            ->method('writeAtomic')
            ->with($file, $expectedContent)
            ->willReturn(FilesystemResult::success());

        $editor->append($file, 'new');
    }
}
<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\LocalSafeFilesystem;
use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\PhpErrorLoggerInterface;

/**
 * Tests du job MOVE du SafeFilesystem.
 */
final class MoveTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_move_' . uniqid('', true);
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

    public function test_move_successfully_moves_file(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/moved.queue';

        file_put_contents($source, 'ok');

        $result = $fs->move($source, $destination);

        $this->assertTrue($result->isSuccess());
        $this->assertFileDoesNotExist($source);
        $this->assertFileExists($destination);
    }

    public function test_move_fails_if_source_does_not_exist(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/missing.queue';
        $destination = $this->dir . '/dest.queue';

        $result = $fs->move($source, $destination);

        $this->assertTrue($result->isFailure());
        $this->assertFileDoesNotExist($destination);
    }

    public function test_move_does_not_overwrite_existing_file(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/dest.queue';

        file_put_contents($source, 'source');
        file_put_contents($destination, 'existing');

        $result = $fs->move($source, $destination);

        $this->assertTrue($result->isFailure());
        $this->assertSame('existing', file_get_contents($destination));
        $this->assertFileExists($source);
    }

    public function test_move_is_atomic_like_behavior(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/dest.queue';

        file_put_contents($source, 'ok');

        $result = $fs->move($source, $destination);

        $this->assertTrue($result->isSuccess());

        $this->assertFalse(
            file_exists($source) && file_exists($destination)
        );
    }

    public function test_move_handles_invalid_destination_path(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = '/invalid/path/dest.queue';

        file_put_contents($source, 'ok');

        $result = $fs->move($source, $destination);

        $this->assertTrue($result->isFailure());
        $this->assertFileExists($source);
    }
}
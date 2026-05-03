<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\LocalSafeFilesystem;
use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\PhpErrorLoggerInterface;

/**
 * Tests de robustesse MOVE face aux crashs.
 */
final class MoveCrashTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_move_crash_' . uniqid('', true);
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

    public function test_file_is_never_duplicated(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/processing.queue';

        file_put_contents($source, 'ok');

        $fs->move($source, $destination);

        $existsSource = file_exists($source);
        $existsDestination = file_exists($destination);

        $this->assertFalse($existsSource && $existsDestination);
    }

    public function test_file_is_never_lost(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/processing.queue';

        file_put_contents($source, 'ok');

        $fs->move($source, $destination);

        $existsSource = file_exists($source);
        $existsDestination = file_exists($destination);

        $this->assertTrue($existsSource || $existsDestination);
    }

    public function test_content_is_preserved_after_move(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/processing.queue';

        $content = json_encode(['id' => 123]);

        file_put_contents($source, $content);

        $fs->move($source, $destination);

        if (file_exists($destination)) {
            $this->assertSame($content, file_get_contents($destination));
        } else {
            $this->assertSame($content, file_get_contents($source));
        }
    }

    public function test_move_failure_keeps_source_intact(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = '/invalid/path/file.queue';

        file_put_contents($source, 'ok');

        $result = $fs->move($source, $destination);

        $this->assertTrue($result->isFailure());
        $this->assertFileExists($source);
    }

    public function test_move_does_not_create_partial_file(): void
    {
        $fs = $this->createFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/processing.queue';

        file_put_contents($source, 'ok');

        $fs->move($source, $destination);

        if (file_exists($destination)) {
            $this->assertNotEmpty(file_get_contents($destination));
        }
    }
}
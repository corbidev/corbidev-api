<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\LocalSafeFilesystem;
use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\PhpErrorLoggerInterface;

/**
 * Test de concurrence simulée sur MOVE.
 */
final class MoveConcurrencyTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_concurrent_' . uniqid('', true);
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

    public function test_only_one_process_can_move_file(): void
    {
        $fsA = $this->createFilesystem();
        $fsB = $this->createFilesystem();

        $source = $this->dir . '/job.queue';
        $destination = $this->dir . '/processing.queue';

        file_put_contents($source, 'job');

        $resultA = $fsA->move($source, $destination);
        $resultB = $fsB->move($source, $destination);

        $this->assertTrue(
            $resultA->isSuccess() xor $resultB->isSuccess(),
            'Only one process should succeed'
        );

        $existsSource = file_exists($source);
        $existsDestination = file_exists($destination);

        $this->assertTrue($existsSource || $existsDestination);
        $this->assertFalse($existsSource && $existsDestination);
    }

    public function test_second_process_fails_cleanly(): void
    {
        $fsA = $this->createFilesystem();
        $fsB = $this->createFilesystem();

        $source = $this->dir . '/job.queue';
        $destination = $this->dir . '/processing.queue';

        file_put_contents($source, 'job');

        $resultA = $fsA->move($source, $destination);
        $resultB = $fsB->move($source, $destination);

        $this->assertTrue(
            $resultA->isSuccess() || $resultB->isSuccess()
        );

        $this->assertTrue(
            $resultA->isFailure() || $resultB->isFailure()
        );

        $this->assertFalse(
            file_exists($source) && file_exists($destination)
        );
    }

    public function test_no_duplicate_processing_possible(): void
    {
        $fsA = $this->createFilesystem();
        $fsB = $this->createFilesystem();

        $source = $this->dir . '/job.queue';
        $destination = $this->dir . '/processing.queue';

        file_put_contents($source, 'job');

        $fsA->move($source, $destination);
        $fsB->move($source, $destination);

        $files = glob($this->dir . '/*.queue');

        $this->assertCount(1, $files);
    }
}
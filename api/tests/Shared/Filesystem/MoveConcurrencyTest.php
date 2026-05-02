<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Filesystem\LocalSafeFilesystem;

/**
 * Test de concurrence simulée sur MOVE.
 *
 * Objectif :
 * Vérifier que deux processus concurrents ne peuvent pas
 * traiter le même fichier.
 *
 * Important :
 * On simule la concurrence séquentiellement, car rename()
 * est atomique au niveau OS.
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

    public function test_only_one_process_can_move_file(): void
    {
        $fsA = new LocalSafeFilesystem();
        $fsB = new LocalSafeFilesystem();

        $source = $this->dir . '/job.queue';
        $destination = $this->dir . '/processing.queue';

        file_put_contents($source, 'job');

        // 🧠 simulation : deux crons tentent le move
        $resultA = $fsA->move($source, $destination);
        $resultB = $fsB->move($source, $destination);

        // ✔ un seul doit réussir
        $this->assertTrue(
            $resultA->success xor $resultB->success,
            'Only one process should succeed'
        );

        // ✔ le fichier existe exactement une fois
        $existsSource = file_exists($source);
        $existsDestination = file_exists($destination);

        $this->assertTrue($existsSource || $existsDestination);
        $this->assertFalse($existsSource && $existsDestination);
    }

    public function test_second_process_fails_cleanly(): void
    {
        $fsA = new LocalSafeFilesystem();
        $fsB = new LocalSafeFilesystem();

        $source = $this->dir . '/job.queue';
        $destination = $this->dir . '/processing.queue';

        file_put_contents($source, 'job');

        $resultA = $fsA->move($source, $destination);
        $resultB = $fsB->move($source, $destination);

        // ✔ un succès, un échec
        $this->assertTrue($resultA->success || $resultB->success);
        $this->assertTrue(!$resultA->success || !$resultB->success);

        // ✔ aucun état incohérent
        $this->assertFalse(
            file_exists($source) && file_exists($destination)
        );
    }

    public function test_no_duplicate_processing_possible(): void
    {
        $fsA = new LocalSafeFilesystem();
        $fsB = new LocalSafeFilesystem();

        $source = $this->dir . '/job.queue';
        $destination = $this->dir . '/processing.queue';

        file_put_contents($source, 'job');

        $fsA->move($source, $destination);
        $fsB->move($source, $destination);

        // ✔ il n'existe qu'un seul fichier exploitable
        $files = glob($this->dir . '/*.queue');

        $this->assertCount(1, $files);
    }
}
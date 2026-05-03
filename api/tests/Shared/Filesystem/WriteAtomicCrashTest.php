<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use App\Shared\Infrastructure\Filesystem\LocalSafeFilesystem;

/**
 * Test de robustesse face à un crash pendant writeAtomic.
 *
 * Objectif :
 * Vérifier qu’un fichier temporaire ne fuit jamais dans le système.
 */
final class WriteAtomicCrashTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fs_crash_' . uniqid('', true);
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

    public function test_tmp_file_left_is_not_exposed_by_list(): void
    {
        $fs = new LocalSafeFilesystem();

        $finalPath = $this->dir . '/test.queue';
        $tmpPath = $finalPath . '.crash.tmp';

        // 🔥 simulation crash : fichier temporaire présent
        file_put_contents($tmpPath, '{"invalid": ');

        // ✔ aucun fichier final
        $this->assertFileDoesNotExist($finalPath);

        // ✔ tmp existe
        $this->assertFileExists($tmpPath);

        // ✔ LIST ne doit PAS retourner ce fichier
        $files = $fs->list($this->dir, '*.queue');

        $this->assertSame([], $files); // 🔥 assertion stricte
    }
}
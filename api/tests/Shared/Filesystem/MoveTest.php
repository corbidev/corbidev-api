<?php

declare(strict_types=1);

namespace Tests\Shared\Filesystem;

use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Filesystem\LocalSafeFilesystem;

/**
 * Tests du job MOVE du SafeFilesystem.
 *
 * Objectif :
 * Garantir un déplacement atomique basé uniquement sur rename,
 * sans fallback, pour éviter toute duplication ou incohérence.
 *
 * Pourquoi :
 * MOVE est utilisé comme mécanisme de lock implicite dans la queue :
 * pending → processing
 *
 * Si MOVE est incorrect :
 * - double traitement possible
 * - perte de données
 * - corruption du flux
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
                unlink($file);
            }
        }

        rmdir($this->dir);
    }

    public function test_move_successfully_moves_file(): void
    {
        $fs = new LocalSafeFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/moved.queue';

        file_put_contents($source, 'ok');

        $result = $fs->move($source, $destination);

        $this->assertTrue($result->success);

        $this->assertFileDoesNotExist($source);
        $this->assertFileExists($destination);
    }

    public function test_move_fails_if_source_does_not_exist(): void
    {
        $fs = new LocalSafeFilesystem();

        $source = $this->dir . '/missing.queue';
        $destination = $this->dir . '/dest.queue';

        $result = $fs->move($source, $destination);

        $this->assertFalse($result->success);

        $this->assertFileDoesNotExist($destination);
    }

    public function test_move_does_not_overwrite_existing_file(): void
    {
        $fs = new LocalSafeFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/dest.queue';

        file_put_contents($source, 'source');
        file_put_contents($destination, 'existing');

        $result = $fs->move($source, $destination);

        $this->assertFalse($result->success);

        // le fichier destination reste intact
        $this->assertSame('existing', file_get_contents($destination));

        // la source existe toujours
        $this->assertFileExists($source);
    }

    public function test_move_is_atomic_like_behavior(): void
    {
        $fs = new LocalSafeFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = $this->dir . '/dest.queue';

        file_put_contents($source, 'ok');

        $result = $fs->move($source, $destination);

        $this->assertTrue($result->success);

        // jamais les deux en même temps
        $this->assertFalse(
            file_exists($source) && file_exists($destination)
        );
    }

    public function test_move_handles_invalid_destination_path(): void
    {
        $fs = new LocalSafeFilesystem();

        $source = $this->dir . '/file.queue';
        $destination = '/invalid/path/dest.queue';

        file_put_contents($source, 'ok');

        $result = $fs->move($source, $destination);

        $this->assertFalse($result->success);

        // la source reste intacte
        $this->assertFileExists($source);
    }
}
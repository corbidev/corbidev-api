<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filesystem;

use App\Shared\Infrastructure\Logging\Emergency\EmergencyLogger;
use App\Shared\Infrastructure\Logging\Emergency\NativePhpErrorLogger;

/**
 * Implémentation locale d'un filesystem sécurisé.
 *
 * Objectif :
 * Fournir des opérations filesystem déterministes, atomiques et sûres,
 * sans jamais exposer d'état intermédiaire (fichier partiel, corruption).
 *
 * Contraintes :
 * - aucune exception ne doit remonter
 * - aucune logique métier
 * - aucun système de logging interne
 * - fallback uniquement via EmergencyLogger
 */
final class LocalSafeFilesystem implements FilesystemInterface
{
    private EmergencyLogger $emergencyLogger;

    public function __construct(?EmergencyLogger $emergencyLogger = null)
    {
        $this->emergencyLogger = $emergencyLogger
            ?? new EmergencyLogger(new NativePhpErrorLogger());
    }

    public function writeAtomic(string $targetPath, string $content): FilesystemResult
    {
        $tmpPath = $targetPath . '.' . uniqid('', true) . '.tmp';

        try {
            $bytes = @file_put_contents($tmpPath, $content, LOCK_EX);

            if ($bytes === false) {
                return $this->fail("write failed: $tmpPath");
            }

            if ($bytes !== strlen($content)) {
                @unlink($tmpPath);
                return $this->fail("partial write detected: $tmpPath");
            }

            $result = @rename($tmpPath, $targetPath);

            if ($result === false) {
                @unlink($tmpPath);
                return $this->fail("rename failed: $tmpPath → $targetPath");
            }

            return FilesystemResult::success();

        } catch (\Throwable $e) {
            @unlink($tmpPath);
            return $this->fail($e->getMessage());
        }
    }

    public function list(string $directory, string $pattern = '*'): array
    {
        try {
            if (!@is_dir($directory)) {
                return [];
            }

            $fullPattern = rtrim($directory, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . $pattern;

            $files = @glob($fullPattern);

            if (!is_array($files)) {
                $this->logError("glob failed: $directory with pattern $pattern");
                return [];
            }

            $files = array_filter(
                $files,
                static fn(string $file): bool => @is_file($file)
            );

            sort($files, SORT_STRING);

            return array_values($files);

        } catch (\Throwable $e) {
            $this->logError($e->getMessage());
            return [];
        }
    }

    public function move(string $source, string $destination): FilesystemResult
    {
        try {
            if (!@is_file($source)) {
                return $this->fail("move failed: source not found: $source");
            }

            if (@file_exists($destination)) {
                return $this->fail("move failed: destination exists: $destination");
            }

            // 🔥 FIX CRITIQUE : vérifier le dossier cible AVANT rename
            $destinationDir = dirname($destination);

            if (!@is_dir($destinationDir)) {
                return $this->fail("move failed: destination directory not found: $destinationDir");
            }

            $result = @rename($source, $destination);

            if ($result === false) {
                return $this->fail("move failed: rename error: $source → $destination");
            }

            return FilesystemResult::success();

        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function delete(string $path): FilesystemResult
    {
        try {
            if (!@file_exists($path)) {
                return FilesystemResult::success();
            }

            if (!@is_file($path)) {
                return $this->fail("delete failed: not a file: $path");
            }

            $result = @unlink($path);

            if ($result === false) {
                return $this->fail("delete failed: unlink error: $path");
            }

            return FilesystemResult::success();

        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    private function fail(string $message): FilesystemResult
    {
        $this->logError($message);
        return FilesystemResult::failure($message);
    }

    private function logError(string $message): void
    {
        $this->emergencyLogger->log('[Filesystem] ' . $message);
    }
}
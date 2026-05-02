<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Filesystem;

/**
 * Implémentation locale d'un filesystem sécurisé.
 *
 * Objectif :
 * Fournir des opérations filesystem déterministes, atomiques et sûres,
 * sans jamais exposer d'état intermédiaire (fichier partiel, corruption).
 *
 * Contraintes :
 * - aucune exception ne doit remonter
 * - toutes les erreurs sont loggées
 * - comportement toujours prédictible
 *
 * Cette classe est utilisée comme brique technique de bas niveau
 * (ex: queue logging), sans contenir aucune logique métier.
 */
final class LocalSafeFilesystem
{
    /**
     * Chemin du fichier de log technique.
     *
     * Pourquoi :
     * Centraliser toutes les erreurs filesystem sans impacter
     * le système appelant.
     */
    private string $logPath;

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath
            ?? __DIR__ . '/../../../../var/log/errorSystem/filesystem.log';
    }

    /**
     * Écrit un fichier de manière atomique.
     *
     * Pourquoi :
     * Garantir qu'un fichier n'est jamais visible dans un état partiel,
     * même en cas de crash (JSON invalide, écriture interrompue).
     *
     * Stratégie :
     * - écriture dans un fichier temporaire unique (.tmp)
     * - vérification complète de l'écriture
     * - renommage atomique vers la destination finale
     *
     * @param string $targetPath
     * @param string $content
     *
     * @return FilesystemResult
     */
    public function writeAtomic(string $targetPath, string $content): FilesystemResult
    {
        $tmpPath = $targetPath . '.' . uniqid('', true) . '.tmp';

        try {
            // 1. Écriture dans le fichier temporaire
            $bytes = @file_put_contents($tmpPath, $content, LOCK_EX);

            if ($bytes === false) {
                return $this->fail("write failed: $tmpPath");
            }

            // 2. Vérification anti écriture partielle
            if ($bytes !== strlen($content)) {
                @unlink($tmpPath);
                return $this->fail("partial write detected: $tmpPath");
            }

            // 3. Rename atomique
            if (!@rename($tmpPath, $targetPath)) {
                @unlink($tmpPath);
                return $this->fail("rename failed: $tmpPath → $targetPath");
            }

            return FilesystemResult::success();

        } catch (\Throwable $e) {
            @unlink($tmpPath);

            return $this->fail($e->getMessage());
        }
    }

    /**
     * Liste les fichiers exploitables d'un dossier.
     *
     * Pourquoi :
     * Permettre aux composants (ex: queue) de récupérer uniquement
     * des fichiers stables (.queue), sans exposer les fichiers temporaires.
     *
     * Règles :
     * - uniquement les fichiers .queue
     * - chemins complets
     * - tri déterministe
     * - aucune exception
     *
     * @param string $directory
     *
     * @return array<string>
     */
    public function list(string $directory): array
    {
        try {
            if (!is_dir($directory)) {
                return [];
            }

            $pattern = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.queue';

            $files = glob($pattern);

            if ($files === false) {
                $this->logError("glob failed: $directory");
                return [];
            }

            $files = array_filter($files, static function ($file) {
                return is_file($file);
            });

            sort($files, SORT_STRING);

            return array_values($files);

        } catch (\Throwable $e) {
            $this->logError($e->getMessage());

            return [];
        }
    }

    /**
     * Centralise la gestion des erreurs.
     *
     * Pourquoi :
     * Uniformiser le comportement d'échec et garantir
     * un logging systématique.
     */
    private function fail(string $message): FilesystemResult
    {
        $this->logError($message);

        return FilesystemResult::failure($message);
    }

    /**
     * Écrit une erreur technique dans le log filesystem.
     *
     * Pourquoi :
     * Permettre le diagnostic en production sans exposer
     * d'erreurs au système métier.
     */
    private function logError(string $message): void
    {
        $line = sprintf(
            "[%s] %s\n",
            date('Y-m-d H:i:s'),
            $message
        );

        $dir = dirname($this->logPath);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        @file_put_contents(
            $this->logPath,
            $line,
            FILE_APPEND
        );
    }
/**
 * Déplace un fichier de manière atomique via rename.
 *
 * Pourquoi :
 * Fournir un mécanisme de lock implicite (ex: queue),
 * garantissant qu'un fichier n'est traité qu'une seule fois.
 *
 * Contraintes :
 * - rename uniquement (aucun fallback copy)
 * - aucun overwrite autorisé
 * - aucune exception remontée
 *
 * @param string $source
 * @param string $destination
 *
 * @return FilesystemResult
 */
public function move(string $source, string $destination): FilesystemResult
{
    try {
        // ✔ source doit exister
        if (!is_file($source)) {
            return $this->fail("move failed: source not found: $source");
        }

        // ✔ destination ne doit pas exister
        if (file_exists($destination)) {
            return $this->fail("move failed: destination exists: $destination");
        }

        // ✔ tentative de rename atomique
        if (!@rename($source, $destination)) {
            return $this->fail("move failed: rename error: $source → $destination");
        }

        return FilesystemResult::success();

    } catch (\Throwable $e) {
        return $this->fail($e->getMessage());
    }
}
/**
 * Supprime un fichier de manière sûre et idempotente.
 *
 * Pourquoi :
 * Garantir qu'un fichier peut être supprimé sans risque,
 * même s'il est déjà absent ou en état inattendu.
 *
 * Règles :
 * - idempotent : fichier absent = succès
 * - refuse de supprimer autre chose qu'un fichier
 * - aucune exception remontée
 *
 * @param string $path
 *
 * @return FilesystemResult
 */
public function delete(string $path): FilesystemResult
{
    try {
        // ✔ déjà absent → OK (idempotence)
        if (!file_exists($path)) {
            return FilesystemResult::success();
        }

        // ✔ sécurité : refuser dossiers / autres
        if (!is_file($path)) {
            return $this->fail("delete failed: not a file: $path");
        }

        // ✔ suppression
        if (!@unlink($path)) {
            return $this->fail("delete failed: unlink error: $path");
        }

        return FilesystemResult::success();

    } catch (\Throwable $e) {
        return $this->fail($e->getMessage());
    }
}
}
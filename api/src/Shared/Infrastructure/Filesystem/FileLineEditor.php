<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filesystem;

/**
 * Manipulateur générique de fichiers ligne par ligne.
 *
 * Objectif :
 * Permettre des modifications sûres (append, prepend, replace, delete)
 * en garantissant une écriture atomique via le Filesystem.
 *
 * Pourquoi :
 * Les opérations ligne par ligne ne sont pas atomiques nativement.
 * On impose donc une stratégie :
 * read → transform → writeAtomic
 *
 * Contraintes :
 * - aucune exception remontée
 * - aucune logique métier
 * - toujours passer par writeAtomic
 */
final class FileLineEditor
{
    public function __construct(
        private readonly FilesystemInterface $filesystem
    ) {
    }

    /**
     * Ajoute une ligne en fin de fichier.
     *
     * Pourquoi :
     * Permettre un append sans altérer l’ordre existant.
     */
    public function append(string $path, string $line): FilesystemResult
    {
        try {
            $lines = $this->readLines($path);
            $lines[] = $line;

            return $this->write($path, $lines);
        } catch (\Throwable) {
            return FilesystemResult::failure('append failed');
        }
    }

    /**
     * Ajoute une ligne en début de fichier.
     *
     * Pourquoi :
     * Permettre une insertion prioritaire.
     */
    public function prepend(string $path, string $line): FilesystemResult
    {
        try {
            $lines = $this->readLines($path);
            array_unshift($lines, $line);

            return $this->write($path, $lines);
        } catch (\Throwable) {
            return FilesystemResult::failure('prepend failed');
        }
    }

    /**
     * Modifie les lignes via une fonction de transformation.
     *
     * @param callable(string): string $modifier
     *
     * Pourquoi :
     * Permettre des transformations génériques sans dépendance métier.
     */
    public function replace(string $path, callable $modifier): FilesystemResult
    {
        try {
            $lines = $this->readLines($path);

            $lines = array_map(
                static function (string $line) use ($modifier): string {
                    try {
                        return $modifier($line);
                    } catch (\Throwable) {
                        // fallback strict : ne jamais casser une ligne
                        return $line;
                    }
                },
                $lines
            );

            return $this->write($path, $lines);
        } catch (\Throwable) {
            return FilesystemResult::failure('replace failed');
        }
    }

    /**
     * Supprime des lignes selon un filtre.
     *
     * @param callable(string): bool $filter retourne true pour supprimer
     *
     * Pourquoi :
     * Permettre une suppression contrôlée sans logique métier.
     */
    public function delete(string $path, callable $filter): FilesystemResult
    {
        try {
            $lines = $this->readLines($path);

            $lines = array_values(array_filter(
                $lines,
                static function (string $line) use ($filter): bool {
                    try {
                        return !$filter($line);
                    } catch (\Throwable) {
                        // fallback strict : ne jamais supprimer si doute
                        return true;
                    }
                }
            ));

            return $this->write($path, $lines);
        } catch (\Throwable) {
            return FilesystemResult::failure('delete failed');
        }
    }

    /**
     * Lecture sécurisée des lignes.
     *
     * Pourquoi :
     * Toujours retourner un tableau stable (même si fichier absent,
     * illisible, corrompu ou inaccessible).
     *
     * @return array<string>
     */
    private function readLines(string $path): array
    {
        try {
            // 🔥 FIX CRITIQUE : vérifier existence + lisibilité
            if (!@is_file($path) || !@is_readable($path)) {
                return [];
            }

            // 🔥 lecture protégée (aucun warning possible)
            $content = @file_get_contents($path);

            if ($content === false || $content === '') {
                return [];
            }

            $normalized = rtrim($content, "\n");

            if ($normalized === '') {
                return [];
            }

            return explode("\n", $normalized);

        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Écriture atomique des lignes.
     *
     * Pourquoi :
     * Centraliser la conversion lignes → string
     * et garantir un format stable.
     *
     * @param array<string> $lines
     */
    private function write(string $path, array $lines): FilesystemResult
    {
        try {
            $content = implode("\n", $lines) . "\n";

            return $this->filesystem->writeAtomic($path, $content);
        } catch (\Throwable) {
            return FilesystemResult::failure('write failed');
        }
    }
}
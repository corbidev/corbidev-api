<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filesystem;

/**
 * Contrat du filesystem technique.
 *
 * Objectif :
 * Définir les opérations de base sur le système de fichiers
 * de manière abstraite et testable.
 *
 * Pourquoi :
 * - Permettre le découplage entre les composants (ex: FileLineEditor)
 *   et l’implémentation réelle (LocalSafeFilesystem)
 * - Faciliter les tests via des mocks
 * - Garantir un comportement uniforme (résultats + erreurs)
 *
 * Contraintes :
 * - aucune logique métier
 * - aucune exception remontée (retour via FilesystemResult)
 * - opérations déterministes
 */
interface FilesystemInterface
{
    /**
     * Écrit un fichier de manière atomique.
     *
     * Pourquoi :
     * Garantir qu’un fichier n’est jamais dans un état partiel.
     *
     * @param string $targetPath Chemin du fichier cible
     * @param string $content Contenu complet à écrire
     *
     * @return FilesystemResult Succès ou échec
     */
    public function writeAtomic(string $targetPath, string $content): FilesystemResult;

    /**
     * Liste les fichiers d’un dossier selon un pattern.
     *
     * Pourquoi :
     * Fournir un accès générique sans connaissance métier.
     *
     * @param string $directory Dossier cible
     * @param string $pattern Pattern glob (ex: *.json, *.log, *)
     *
     * @return array<string> Liste des chemins complets
     */
    public function list(string $directory, string $pattern = '*'): array;

    /**
     * Déplace un fichier de manière atomique.
     *
     * Pourquoi :
     * Garantir un déplacement sûr sans duplication ni perte.
     *
     * @param string $source Fichier source
     * @param string $destination Fichier destination
     *
     * @return FilesystemResult Succès ou échec
     */
    public function move(string $source, string $destination): FilesystemResult;

    /**
     * Supprime un fichier de manière sûre et idempotente.
     *
     * Pourquoi :
     * Permettre une suppression sans erreur même si le fichier n’existe pas.
     *
     * @param string $path Chemin du fichier
     *
     * @return FilesystemResult Succès ou échec
     */
    public function delete(string $path): FilesystemResult;
}
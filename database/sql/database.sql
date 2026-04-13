-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : database
-- Généré le : lun. 13 avr. 2026 à 20:53
-- Version du serveur : 11.7.2-MariaDB-ubu2404
-- Version de PHP : 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `symfony-api`
--
DROP DATABASE IF EXISTS `symfony-api`;
CREATE DATABASE IF NOT EXISTS `symfony-api` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
USE `symfony-api`;

-- --------------------------------------------------------

--
-- Structure de la table `corbidev_api_auth_credential`
--

DROP TABLE IF EXISTS `corbidev_api_auth_credential`;
CREATE TABLE `corbidev_api_auth_credential` (
  `id` bigint(20) NOT NULL,
  `client_secret_hash` varchar(255) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `api_key` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `corbidev_api_log_entry`
--

DROP TABLE IF EXISTS `corbidev_api_log_entry`;
CREATE TABLE `corbidev_api_log_entry` (
  `id` bigint(20) NOT NULL,
  `ts` datetime NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `http_status` smallint(6) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `fingerprint` char(64) DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `created_at` datetime NOT NULL,
  `level_id` smallint(6) NOT NULL,
  `source_id` bigint(20) NOT NULL,
  `env_id` smallint(6) NOT NULL,
  `url_id` bigint(20) DEFAULT NULL,
  `uri_id` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `corbidev_api_log_entry_tag`
--

DROP TABLE IF EXISTS `corbidev_api_log_entry_tag`;
CREATE TABLE `corbidev_api_log_entry_tag` (
  `log_entry_id` bigint(20) NOT NULL,
  `tag_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `corbidev_api_log_env`
--

DROP TABLE IF EXISTS `corbidev_api_log_env`;
CREATE TABLE `corbidev_api_log_env` (
  `id` smallint(6) NOT NULL,
  `name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `corbidev_api_log_level`
--

DROP TABLE IF EXISTS `corbidev_api_log_level`;
CREATE TABLE `corbidev_api_log_level` (
  `id` smallint(6) NOT NULL,
  `name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `corbidev_api_log_tag`
--

DROP TABLE IF EXISTS `corbidev_api_log_tag`;
CREATE TABLE `corbidev_api_log_tag` (
  `id` bigint(20) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `corbidev_api_log_uri`
--

DROP TABLE IF EXISTS `corbidev_api_log_uri`;
CREATE TABLE `corbidev_api_log_uri` (
  `id` bigint(20) NOT NULL,
  `url_id` bigint(20) NOT NULL,
  `uri` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `corbidev_api_log_url`
--

DROP TABLE IF EXISTS `corbidev_api_log_url`;
CREATE TABLE `corbidev_api_log_url` (
  `id` bigint(20) NOT NULL,
  `url` varchar(768) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `corbidev_api_auth_credential`
--
ALTER TABLE `corbidev_api_auth_credential`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`);

--
-- Index pour la table `corbidev_api_log_entry`
--
ALTER TABLE `corbidev_api_log_entry`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ts` (`ts`),
  ADD KEY `idx_level` (`level_id`),
  ADD KEY `idx_source` (`source_id`),
  ADD KEY `idx_env` (`env_id`),
  ADD KEY `idx_fingerprint` (`fingerprint`),
  ADD KEY `idx_url_id` (`url_id`),
  ADD KEY `idx_uri_id` (`uri_id`),
  ADD KEY `idx_level_ts` (`level_id`,`ts`),
  ADD KEY `idx_source_ts` (`source_id`,`ts`),
  ADD KEY `idx_env_ts` (`env_id`,`ts`);

--
-- Index pour la table `corbidev_api_log_entry_tag`
--
ALTER TABLE `corbidev_api_log_entry_tag`
  ADD PRIMARY KEY (`log_entry_id`,`tag_id`),
  ADD KEY `IDX_F21DC3E5D465829D` (`log_entry_id`),
  ADD KEY `fk_tag` (`tag_id`);

--
-- Index pour la table `corbidev_api_log_env`
--
ALTER TABLE `corbidev_api_log_env`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `corbidev_api_log_level`
--
ALTER TABLE `corbidev_api_log_level`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `corbidev_api_log_tag`
--
ALTER TABLE `corbidev_api_log_tag`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `corbidev_api_log_uri`
--
ALTER TABLE `corbidev_api_log_uri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uri` (`uri`),
  ADD KEY `idx_log_uri_url_id` (`url_id`);

--
-- Index pour la table `corbidev_api_log_url`
--
ALTER TABLE `corbidev_api_log_url`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `url` (`url`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `corbidev_api_auth_credential`
--
ALTER TABLE `corbidev_api_auth_credential`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `corbidev_api_log_entry`
--
ALTER TABLE `corbidev_api_log_entry`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `corbidev_api_log_tag`
--
ALTER TABLE `corbidev_api_log_tag`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `corbidev_api_log_uri`
--
ALTER TABLE `corbidev_api_log_uri`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `corbidev_api_log_url`
--
ALTER TABLE `corbidev_api_log_url`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `corbidev_api_log_entry`
--
ALTER TABLE `corbidev_api_log_entry`
  ADD CONSTRAINT `FK_9673EFD618AD1504` FOREIGN KEY (`env_id`) REFERENCES `corbidev_api_log_env` (`id`),
  ADD CONSTRAINT `FK_9673EFD65FB14BA7` FOREIGN KEY (`level_id`) REFERENCES `corbidev_api_log_level` (`id`),
  ADD CONSTRAINT `FK_9673EFD681CFDAE7` FOREIGN KEY (`url_id`) REFERENCES `corbidev_api_log_url` (`id`),
  ADD CONSTRAINT `FK_9673EFD6B6112AD5` FOREIGN KEY (`uri_id`) REFERENCES `corbidev_api_log_uri` (`id`),
  ADD CONSTRAINT `fk_log_source` FOREIGN KEY (`source_id`) REFERENCES `corbidev_api_auth_credential` (`id`);

--
-- Contraintes pour la table `corbidev_api_log_entry_tag`
--
ALTER TABLE `corbidev_api_log_entry_tag`
  ADD CONSTRAINT `FK_F21DC3E5BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `corbidev_api_log_tag` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_F21DC3E5D465829D` FOREIGN KEY (`log_entry_id`) REFERENCES `corbidev_api_log_entry` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `corbidev_api_log_uri`
--
ALTER TABLE `corbidev_api_log_uri`
  ADD CONSTRAINT `fk_log_uri_url` FOREIGN KEY (`url_id`) REFERENCES `corbidev_api_log_url` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

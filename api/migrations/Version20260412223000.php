<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prefixed log tables with SQL_PREFIXE and moved source secrets into an independent auth_credential table.';
    }

    public function up(Schema $schema): void
    {
        $prefix = $this->getSqlPrefix();
        $hasLegacyClientSecret = $schema->hasTable('log_source') && $schema->getTable('log_source')->hasColumn('client_secret');
        $hasPrefixedClientSecret = $schema->hasTable($prefix . 'log_source') && $schema->getTable($prefix . 'log_source')->hasColumn('client_secret');

        $this->renameLegacyTables($schema, $prefix, false);

        $logSourceTable = $prefix . 'log_source';
        $authCredentialTable = $prefix . 'auth_credential';

        if (!$schema->hasTable($authCredentialTable)) {
            $this->addSql(sprintf(<<<'SQL'
                CREATE TABLE %s (
                    id BIGINT AUTO_INCREMENT NOT NULL,
                    source_id BIGINT NOT NULL,
                    client_secret_hash VARCHAR(255) NOT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    UNIQUE INDEX uniq_auth_credential_source (source_id),
                    PRIMARY KEY(id),
                    CONSTRAINT fk_auth_credential_source FOREIGN KEY (source_id) REFERENCES %s (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL, $authCredentialTable, $logSourceTable));
        }

        if ($hasLegacyClientSecret || $hasPrefixedClientSecret) {
            $this->addSql(sprintf(<<<'SQL'
                INSERT INTO %1$s (source_id, client_secret_hash, is_active, created_at, updated_at)
                SELECT source.id, source.client_secret, source.is_active, NOW(), NOW()
                FROM %2$s source
                WHERE source.client_secret IS NOT NULL
                  AND NOT EXISTS (
                    SELECT 1
                    FROM %1$s credential
                    WHERE credential.source_id = source.id
                  )
            SQL, $authCredentialTable, $logSourceTable));

            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN client_secret', $logSourceTable));
        }
    }

    public function down(Schema $schema): void
    {
        $prefix = $this->getSqlPrefix();
        $logSourceTable = $schema->hasTable($prefix . 'log_source') ? $prefix . 'log_source' : 'log_source';
        $authCredentialTable = $schema->hasTable($prefix . 'auth_credential') ? $prefix . 'auth_credential' : 'auth_credential';

        if ($schema->hasTable($logSourceTable) && !$schema->getTable($logSourceTable)->hasColumn('client_secret')) {
            $this->addSql(sprintf('ALTER TABLE %s ADD client_secret VARCHAR(255) DEFAULT NULL', $logSourceTable));
        }

        if ($schema->hasTable($authCredentialTable) && $schema->hasTable($logSourceTable)) {
            $this->addSql(sprintf(<<<'SQL'
                UPDATE %1$s source
                INNER JOIN %2$s credential ON credential.source_id = source.id
                SET source.client_secret = credential.client_secret_hash
            SQL, $logSourceTable, $authCredentialTable));

            $this->addSql(sprintf('DROP TABLE %s', $authCredentialTable));
        }

        $this->renameLegacyTables($schema, $prefix, true);
    }

    private function renameLegacyTables(Schema $schema, string $prefix, bool $reverse): void
    {
        $tables = [
            'log_level',
            'log_env',
            'log_source',
            'log_url',
            'log_uri',
            'log_tag',
            'log_entry',
            'log_entry_tag',
        ];

        foreach ($tables as $baseTable) {
            $legacyTable = $reverse ? $prefix . $baseTable : $baseTable;
            $targetTable = $reverse ? $baseTable : $prefix . $baseTable;

            if (!$schema->hasTable($legacyTable) || $schema->hasTable($targetTable)) {
                continue;
            }

            $this->addSql(sprintf('RENAME TABLE %s TO %s', $legacyTable, $targetTable));
        }
    }

    private function getSqlPrefix(): string
    {
        return (string) ($_ENV['SQL_PREFIXE'] ?? $_SERVER['SQL_PREFIXE'] ?? '');
    }
}
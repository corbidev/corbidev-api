<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412234500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Merged log_source into auth_credential and repointed log_entry.source_id to the canonical auth table.';
    }

    public function up(Schema $schema): void
    {
        $prefix = $this->getSqlPrefix();
        $authTable = $prefix . 'auth_credential';
        $sourceTable = $prefix . 'log_source';
        $logEntryTable = $prefix . 'log_entry';
        $logEntryTagTable = $prefix . 'log_entry_tag';

        if (!$schema->hasTable($authTable) && !$schema->hasTable($sourceTable)) {
            return;
        }

        if (!$schema->hasTable($authTable)) {
            $this->addSql(sprintf(<<<'SQL'
                CREATE TABLE %s (
                    id BIGINT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    api_key VARCHAR(64) NOT NULL,
                    client_secret_hash VARCHAR(255) NOT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    UNIQUE INDEX api_key (api_key),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL, $authTable));
        }

        if ($schema->hasTable($authTable)) {
            $authSchema = $schema->getTable($authTable);

            if (!$authSchema->hasColumn('name')) {
                $this->addSql(sprintf(<<<'SQL'
                    ALTER TABLE %s
                        ADD name VARCHAR(100) NOT NULL DEFAULT '',
                        ADD type VARCHAR(50) NOT NULL DEFAULT 'backend',
                        ADD api_key VARCHAR(64) NOT NULL DEFAULT ''
                SQL, $authTable));
            }
        }

        if ($schema->hasTable($sourceTable)) {
            if ($schema->hasTable($authTable) && $schema->getTable($authTable)->hasColumn('source_id')) {
                $this->addSql(sprintf(<<<'SQL'
                    UPDATE %1$s auth
                    INNER JOIN %2$s source ON source.id = auth.source_id
                    SET
                        auth.name = source.name,
                        auth.type = source.type,
                        auth.api_key = source.api_key,
                        auth.is_active = source.is_active,
                        auth.created_at = source.created_at
                SQL, $authTable, $sourceTable));

                $this->addSql(sprintf(<<<'SQL'
                    INSERT INTO %1$s (name, type, api_key, client_secret_hash, is_active, created_at, updated_at, source_id)
                    SELECT
                        source.name,
                        source.type,
                        source.api_key,
                        '',
                        source.is_active,
                        source.created_at,
                        source.created_at,
                        source.id
                    FROM %2$s source
                    LEFT JOIN %1$s auth ON auth.source_id = source.id
                    WHERE auth.id IS NULL
                SQL, $authTable, $sourceTable));

                if ($schema->hasTable($logEntryTable)) {
                    $this->addSql(sprintf(<<<'SQL'
                        UPDATE %1$s entry_row
                        INNER JOIN %2$s auth ON auth.source_id = entry_row.source_id
                        SET entry_row.source_id = auth.id
                    SQL, $logEntryTable, $authTable));
                }

                $this->dropForeignKeyToTable($authTable, $sourceTable);
                $this->dropForeignKeyToTable($logEntryTable, $sourceTable);
                $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN source_id', $authTable));
            }

            if ($schema->hasTable($authTable) && !$schema->getTable($authTable)->hasIndex('api_key')) {
                $this->addSql(sprintf('CREATE UNIQUE INDEX api_key ON %s (api_key)', $authTable));
            }

            $this->dropTableIfExists($sourceTable);
        }

        $this->rebuildLogTablesForAuthSource($authTable, $logEntryTable, $logEntryTagTable);
    }

    public function down(Schema $schema): void
    {
        $prefix = $this->getSqlPrefix();
        $authTable = $prefix . 'auth_credential';
        $sourceTable = $prefix . 'log_source';
        $logEntryTable = $prefix . 'log_entry';

        if (!$schema->hasTable($authTable)) {
            return;
        }

        if (!$schema->hasTable($sourceTable)) {
            $this->addSql(sprintf(<<<'SQL'
                CREATE TABLE %s (
                    id BIGINT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    api_key VARCHAR(64) NOT NULL,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at DATETIME NOT NULL,
                    PRIMARY KEY(id),
                    UNIQUE INDEX api_key (api_key)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB
            SQL, $sourceTable));
        }

        if (!$schema->getTable($authTable)->hasColumn('source_id')) {
            $this->addSql(sprintf('ALTER TABLE %s ADD source_id BIGINT DEFAULT NULL', $authTable));
        }

        $this->addSql(sprintf(<<<'SQL'
            INSERT INTO %1$s (name, type, api_key, is_active, created_at)
            SELECT auth.name, auth.type, auth.api_key, auth.is_active, auth.created_at
            FROM %2$s auth
            LEFT JOIN %1$s source ON source.api_key = auth.api_key
            WHERE source.id IS NULL
        SQL, $sourceTable, $authTable));

        $this->addSql(sprintf(<<<'SQL'
            UPDATE %1$s auth
            INNER JOIN %2$s source ON source.api_key = auth.api_key
            SET auth.source_id = source.id
        SQL, $authTable, $sourceTable));

        if ($schema->hasTable($logEntryTable)) {
            $this->dropForeignKeyToTable($logEntryTable, $authTable);
            $this->addSql(sprintf(<<<'SQL'
                UPDATE %1$s entry_row
                INNER JOIN %2$s auth ON auth.id = entry_row.source_id
                SET entry_row.source_id = auth.source_id
            SQL, $logEntryTable, $authTable));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT fk_log_source FOREIGN KEY (source_id) REFERENCES %s (id)', $logEntryTable, $sourceTable));
        }

        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT fk_auth_credential_source FOREIGN KEY (source_id) REFERENCES %s (id) ON DELETE CASCADE', $authTable, $sourceTable));
    }

    private function dropForeignKeyToTable(string $tableName, string $referencedTable): void
    {
        $this->addSql(sprintf("SET @fk_name = (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' AND REFERENCED_TABLE_NAME = '%s' LIMIT 1)", $tableName, $referencedTable));
        $this->addSql(sprintf("SET @drop_fk_sql = IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE %s DROP FOREIGN KEY ', @fk_name))", $tableName));
        $this->addSql('PREPARE drop_fk_stmt FROM @drop_fk_sql');
        $this->addSql('EXECUTE drop_fk_stmt');
        $this->addSql('DEALLOCATE PREPARE drop_fk_stmt');
    }

    private function rebuildLogTablesForAuthSource(string $authTable, string $logEntryTable, string $logEntryTagTable): void
    {
        $logEntryCount = (int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $logEntryTable));
        $logEntryTagCount = (int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $logEntryTagTable));

        $this->abortIf(
            $logEntryCount > 0 || $logEntryTagCount > 0,
            sprintf('La fusion vers %s requiert une reconstruction des tables de logs sur cette instance MariaDB. Les tables %s et %s doivent etre vides.', $authTable, $logEntryTable, $logEntryTagTable)
        );

        $this->addSql(sprintf('DROP TABLE IF EXISTS %s', $logEntryTagTable));
        $this->addSql(sprintf('DROP TABLE IF EXISTS %s', $logEntryTable));

        $this->addSql(sprintf(<<<'SQL'
            CREATE TABLE %s (
                id BIGINT AUTO_INCREMENT NOT NULL,
                ts DATETIME NOT NULL,
                title VARCHAR(255) DEFAULT NULL,
                message TEXT NOT NULL,
                http_status SMALLINT DEFAULT NULL,
                duration_ms INT DEFAULT NULL,
                fingerprint CHAR(64) DEFAULT NULL,
                context JSON DEFAULT NULL,
                created_at DATETIME NOT NULL,
                level_id SMALLINT NOT NULL,
                source_id BIGINT NOT NULL,
                env_id SMALLINT NOT NULL,
                url_id BIGINT DEFAULT NULL,
                uri_id BIGINT DEFAULT NULL,
                INDEX idx_ts (ts),
                INDEX idx_level (level_id),
                INDEX idx_source (source_id),
                INDEX idx_env (env_id),
                INDEX idx_fingerprint (fingerprint),
                INDEX idx_url_id (url_id),
                INDEX idx_uri_id (uri_id),
                INDEX idx_level_ts (level_id, ts),
                INDEX idx_source_ts (source_id, ts),
                INDEX idx_env_ts (env_id, ts),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB
        SQL, $logEntryTable));

        $this->addSql(sprintf(<<<'SQL'
            CREATE TABLE %s (
                log_entry_id BIGINT NOT NULL,
                tag_id BIGINT NOT NULL,
                INDEX IDX_F21DC3E5D465829D (log_entry_id),
                INDEX fk_tag (tag_id),
                PRIMARY KEY(log_entry_id, tag_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_uca1400_ai_ci` ENGINE = InnoDB
        SQL, $logEntryTagTable));

        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT FK_9673EFD65FB14BA7 FOREIGN KEY (level_id) REFERENCES %slog_level (id)', $logEntryTable, $this->getSqlPrefix()));
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT fk_log_source FOREIGN KEY (source_id) REFERENCES %s (id)', $logEntryTable, $authTable));
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT FK_9673EFD618AD1504 FOREIGN KEY (env_id) REFERENCES %slog_env (id)', $logEntryTable, $this->getSqlPrefix()));
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT FK_9673EFD681CFDAE7 FOREIGN KEY (url_id) REFERENCES %slog_url (id)', $logEntryTable, $this->getSqlPrefix()));
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT FK_9673EFD6B6112AD5 FOREIGN KEY (uri_id) REFERENCES %slog_uri (id)', $logEntryTable, $this->getSqlPrefix()));
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT FK_F21DC3E5D465829D FOREIGN KEY (log_entry_id) REFERENCES %s (id) ON DELETE CASCADE', $logEntryTagTable, $logEntryTable));
        $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT FK_F21DC3E5BAD26311 FOREIGN KEY (tag_id) REFERENCES %slog_tag (id) ON DELETE CASCADE', $logEntryTagTable, $this->getSqlPrefix()));
    }

    private function dropTableIfExists(string $tableName): void
    {
        $this->addSql(sprintf('DROP TABLE IF EXISTS %s', $tableName));
    }

    private function getSqlPrefix(): string
    {
        return (string) ($_ENV['SQL_PREFIXE'] ?? $_SERVER['SQL_PREFIXE'] ?? '');
    }
}
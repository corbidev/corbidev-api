<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404142909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE log_entry (id BIGINT AUTO_INCREMENT NOT NULL, ts DATETIME(6) NOT NULL, url LONGTEXT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, message LONGTEXT NOT NULL, http_status SMALLINT DEFAULT NULL, duration_ms INT DEFAULT NULL, fingerprint CHAR(64) DEFAULT NULL, context JSON DEFAULT NULL, created_at DATETIME(6) NOT NULL, level_id SMALLINT NOT NULL, source_id BIGINT NOT NULL, env_id SMALLINT NOT NULL, route_id BIGINT DEFAULT NULL, INDEX IDX_B5F762D34ECB4E6 (route_id), INDEX idx_ts (ts), INDEX idx_level (level_id), INDEX idx_source (source_id), INDEX idx_env (env_id), INDEX idx_fingerprint (fingerprint), INDEX idx_level_ts (level_id, ts), INDEX idx_source_ts (source_id, ts), INDEX idx_env_ts (env_id, ts), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE log_entry_tag (log_entry_id BIGINT NOT NULL, tag_id BIGINT NOT NULL, PRIMARY KEY (log_entry_id, tag_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE log_env (id SMALLINT NOT NULL, name VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_9CA06B225E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE log_level (id SMALLINT NOT NULL, name VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_BA94274E5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE log_route (id BIGINT AUTO_INCREMENT NOT NULL, uri VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_22BACB24841CB121 (uri), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE log_source (id BIGINT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, type VARCHAR(50) NOT NULL, api_key VARCHAR(64) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_4A702CD1C912ED9D (api_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE log_tag (id BIGINT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_6C6C9E585E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql("INSERT INTO log_level (id, name) VALUES (100, 'debug'), (200, 'info'), (300, 'warning'), (400, 'error'), (500, 'critical')");
        $this->addSql("INSERT INTO log_env (id, name) VALUES (1, 'dev'), (2, 'test'), (3, 'prod')");
        $this->addSql('ALTER TABLE log_entry ADD CONSTRAINT FK_B5F762D5FB14BA7 FOREIGN KEY (level_id) REFERENCES log_level (id)');
        $this->addSql('ALTER TABLE log_entry ADD CONSTRAINT FK_B5F762D953C1C61 FOREIGN KEY (source_id) REFERENCES log_source (id)');
        $this->addSql('ALTER TABLE log_entry ADD CONSTRAINT FK_B5F762D18AD1504 FOREIGN KEY (env_id) REFERENCES log_env (id)');
        $this->addSql('ALTER TABLE log_entry ADD CONSTRAINT FK_B5F762D34ECB4E6 FOREIGN KEY (route_id) REFERENCES log_route (id)');
        $this->addSql('ALTER TABLE log_entry_tag ADD CONSTRAINT FK_E637556BD465829D FOREIGN KEY (log_entry_id) REFERENCES log_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE log_entry_tag ADD CONSTRAINT FK_E637556BBAD26311 FOREIGN KEY (tag_id) REFERENCES log_tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_entry DROP FOREIGN KEY FK_B5F762D5FB14BA7');
        $this->addSql('ALTER TABLE log_entry DROP FOREIGN KEY FK_B5F762D953C1C61');
        $this->addSql('ALTER TABLE log_entry DROP FOREIGN KEY FK_B5F762D18AD1504');
        $this->addSql('ALTER TABLE log_entry DROP FOREIGN KEY FK_B5F762D34ECB4E6');
        $this->addSql('ALTER TABLE log_entry_tag DROP FOREIGN KEY FK_E637556BD465829D');
        $this->addSql('ALTER TABLE log_entry_tag DROP FOREIGN KEY FK_E637556BBAD26311');
        $this->addSql('DROP TABLE log_entry');
        $this->addSql('DROP TABLE log_entry_tag');
        $this->addSql('DROP TABLE log_env');
        $this->addSql('DROP TABLE log_level');
        $this->addSql('DROP TABLE log_route');
        $this->addSql('DROP TABLE log_source');
        $this->addSql('DROP TABLE log_tag');
    }
}

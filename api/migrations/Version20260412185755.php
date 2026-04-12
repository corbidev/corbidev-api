<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412185755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_entry RENAME INDEX idx_b5f762ddf47645ae TO IDX_B5F762D81CFDAE7');
        $this->addSql('ALTER TABLE log_entry RENAME INDEX idx_b5f762d2d81a3e6 TO IDX_B5F762DB6112AD5');
        $this->addSql('ALTER TABLE log_entry_tag RENAME INDEX fk_e637556bbad26311 TO IDX_E637556BBAD26311');
        $this->addSql('ALTER TABLE log_source ADD client_secret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE log_uri CHANGE url_id url_id BIGINT NOT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              log_uri
            ADD
              CONSTRAINT FK_EBF998FA81CFDAE7 FOREIGN KEY (url_id) REFERENCES log_url (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE log_uri RENAME INDEX uniq_30d8f3bd841cb121 TO UNIQ_EBF998FA841CB121');
        $this->addSql('ALTER TABLE log_uri RENAME INDEX idx_30d8f3bdf47645ae TO IDX_EBF998FA81CFDAE7');
        $this->addSql('ALTER TABLE log_url RENAME INDEX uniq_4a4ea49e8d93d649 TO UNIQ_9B936C75F47645AE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log_entry RENAME INDEX idx_b5f762d81cfdae7 TO IDX_B5F762DDF47645AE');
        $this->addSql('ALTER TABLE log_entry RENAME INDEX idx_b5f762db6112ad5 TO IDX_B5F762D2D81A3E6');
        $this->addSql('ALTER TABLE log_entry_tag RENAME INDEX idx_e637556bbad26311 TO FK_E637556BBAD26311');
        $this->addSql('ALTER TABLE log_source DROP client_secret');
        $this->addSql('ALTER TABLE log_uri DROP FOREIGN KEY FK_EBF998FA81CFDAE7');
        $this->addSql('ALTER TABLE log_uri CHANGE url_id url_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE log_uri RENAME INDEX uniq_ebf998fa841cb121 TO UNIQ_30D8F3BD841CB121');
        $this->addSql('ALTER TABLE log_uri RENAME INDEX idx_ebf998fa81cfdae7 TO IDX_30D8F3BDF47645AE');
        $this->addSql('ALTER TABLE log_url RENAME INDEX uniq_9b936c75f47645ae TO UNIQ_4A4EA49E8D93D649');
    }
}

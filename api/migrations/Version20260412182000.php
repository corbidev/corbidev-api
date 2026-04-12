<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412182000 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Make log_uri.url_id mandatory and enforce URI attached to URL.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE log_uri u INNER JOIN (SELECT uri_id, MIN(url_id) AS url_id FROM log_entry WHERE uri_id IS NOT NULL AND url_id IS NOT NULL GROUP BY uri_id) m ON m.uri_id = u.id SET u.url_id = m.url_id WHERE u.url_id IS NULL');
        $this->addSql("INSERT INTO log_url (url) SELECT CONCAT('generated://uri/', u.id) FROM log_uri u WHERE u.url_id IS NULL");
        $this->addSql("UPDATE log_uri u INNER JOIN log_url lu ON lu.url = CONCAT('generated://uri/', u.id) SET u.url_id = lu.id WHERE u.url_id IS NULL");

        try {
            $this->connection->executeStatement('ALTER TABLE log_uri CHANGE url_id url_id BIGINT NOT NULL');
            $this->connection->executeStatement('ALTER TABLE log_uri ADD CONSTRAINT FK_30D8F3BDF47645AE FOREIGN KEY (url_id) REFERENCES log_url (id) ON DELETE CASCADE');
        } catch (\Throwable $exception) {
            $this->write('Skipping strict DB constraint on log_uri.url_id because current MariaDB data volume cannot ALTER this table: ' . $exception->getMessage());
        }
    }

    public function down(Schema $schema): void
    {
        try {
            $this->connection->executeStatement('ALTER TABLE log_uri DROP FOREIGN KEY FK_30D8F3BDF47645AE');
            $this->connection->executeStatement('ALTER TABLE log_uri CHANGE url_id url_id BIGINT DEFAULT NULL');
            $this->connection->executeStatement('ALTER TABLE log_uri ADD CONSTRAINT FK_30D8F3BDF47645AE FOREIGN KEY (url_id) REFERENCES log_url (id) ON DELETE SET NULL');
        } catch (\Throwable $exception) {
            $this->write('Skipping down() constraint changes on log_uri.url_id: ' . $exception->getMessage());
        }
    }
}

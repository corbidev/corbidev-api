<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412170000 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Split log_entry route/url into log_uri/log_url references and migrate existing data.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE log_url (id BIGINT AUTO_INCREMENT NOT NULL, url VARCHAR(768) NOT NULL, UNIQUE INDEX UNIQ_4A4EA49E8D93D649 (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE log_uri (id BIGINT AUTO_INCREMENT NOT NULL, url_id BIGINT DEFAULT NULL, uri VARCHAR(255) NOT NULL, INDEX IDX_30D8F3BDF47645AE (url_id), UNIQUE INDEX UNIQ_30D8F3BD841CB121 (uri), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql("INSERT INTO log_url (url) SELECT DISTINCT url FROM log_entry WHERE url IS NOT NULL AND TRIM(url) <> ''");
        $this->addSql('INSERT INTO log_uri (uri) SELECT DISTINCT uri FROM log_route');

        $this->addSql('ALTER TABLE log_entry ADD url_id BIGINT DEFAULT NULL, ADD uri_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE log_entry ADD INDEX IDX_B5F762DDF47645AE (url_id), ADD INDEX IDX_B5F762D2D81A3E6 (uri_id)');

        $this->addSql('UPDATE log_entry e INNER JOIN log_url u ON u.url = e.url SET e.url_id = u.id WHERE e.url IS NOT NULL AND TRIM(e.url) <> ""');
        $this->addSql('UPDATE log_entry e INNER JOIN log_route r ON r.id = e.route_id INNER JOIN log_uri u ON u.uri = r.uri SET e.uri_id = u.id WHERE e.route_id IS NOT NULL');
        $this->addSql('UPDATE log_uri u INNER JOIN (SELECT uri_id, MIN(url_id) AS url_id FROM log_entry WHERE uri_id IS NOT NULL AND url_id IS NOT NULL GROUP BY uri_id) j ON j.uri_id = u.id SET u.url_id = j.url_id');

        $this->addSql('ALTER TABLE log_uri ADD CONSTRAINT FK_30D8F3BDF47645AE FOREIGN KEY (url_id) REFERENCES log_url (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE log_entry ADD CONSTRAINT FK_B5F762DDF47645AE FOREIGN KEY (url_id) REFERENCES log_url (id)');
        $this->addSql('ALTER TABLE log_entry ADD CONSTRAINT FK_B5F762D2D81A3E6 FOREIGN KEY (uri_id) REFERENCES log_uri (id)');

        $this->addSql('ALTER TABLE log_entry DROP FOREIGN KEY FK_B5F762D34ECB4E6');
        $this->addSql('DROP INDEX IDX_B5F762D34ECB4E6 ON log_entry');
        $this->addSql('ALTER TABLE log_entry DROP route_id, DROP url');

        $this->addSql('DROP TABLE log_route');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE log_route (id BIGINT AUTO_INCREMENT NOT NULL, uri VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_22BACB24841CB121 (uri), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('INSERT INTO log_route (uri) SELECT DISTINCT uri FROM log_uri');

        $this->addSql('ALTER TABLE log_entry ADD route_id BIGINT DEFAULT NULL, ADD url LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE log_entry ADD INDEX IDX_B5F762D34ECB4E6 (route_id)');

        $this->addSql('UPDATE log_entry e INNER JOIN log_uri u ON u.id = e.uri_id INNER JOIN log_route r ON r.uri = u.uri SET e.route_id = r.id WHERE e.uri_id IS NOT NULL');
        $this->addSql('UPDATE log_entry e INNER JOIN log_url u ON u.id = e.url_id SET e.url = u.url WHERE e.url_id IS NOT NULL');

        $this->addSql('ALTER TABLE log_entry DROP FOREIGN KEY FK_B5F762DDF47645AE');
        $this->addSql('ALTER TABLE log_entry DROP FOREIGN KEY FK_B5F762D2D81A3E6');
        $this->addSql('DROP INDEX IDX_B5F762DDF47645AE ON log_entry');
        $this->addSql('DROP INDEX IDX_B5F762D2D81A3E6 ON log_entry');
        $this->addSql('ALTER TABLE log_entry DROP url_id, DROP uri_id');

        $this->addSql('ALTER TABLE log_entry ADD CONSTRAINT FK_B5F762D34ECB4E6 FOREIGN KEY (route_id) REFERENCES log_route (id)');

        $this->addSql('ALTER TABLE log_uri DROP FOREIGN KEY FK_30D8F3BDF47645AE');
        $this->addSql('DROP TABLE log_uri');
        $this->addSql('DROP TABLE log_url');
    }
}

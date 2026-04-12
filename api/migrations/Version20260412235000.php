<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412235000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removed transitional defaults from canonical auth_credential source columns.';
    }

    public function up(Schema $schema): void
    {
        $prefix = (string) ($_ENV['SQL_PREFIXE'] ?? $_SERVER['SQL_PREFIXE'] ?? '');
        $authTable = $prefix . 'auth_credential';

        if (!$schema->hasTable($authTable)) {
            return;
        }

        $this->addSql(sprintf('ALTER TABLE %s CHANGE name name VARCHAR(100) NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE api_key api_key VARCHAR(64) NOT NULL', $authTable));
    }

    public function down(Schema $schema): void
    {
        $prefix = (string) ($_ENV['SQL_PREFIXE'] ?? $_SERVER['SQL_PREFIXE'] ?? '');
        $authTable = $prefix . 'auth_credential';

        if (!$schema->hasTable($authTable)) {
            return;
        }

        $this->addSql(sprintf("ALTER TABLE %s CHANGE name name VARCHAR(100) NOT NULL DEFAULT '', CHANGE type type VARCHAR(50) NOT NULL DEFAULT 'backend', CHANGE api_key api_key VARCHAR(64) NOT NULL DEFAULT ''", $authTable));
    }
}
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Deprecated alignment migration kept as a no-op after manual rebuild of empty prefixed auth/log tables.';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
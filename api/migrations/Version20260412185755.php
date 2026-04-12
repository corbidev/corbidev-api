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
        return 'Deprecated migration kept as a no-op. The auth table split and schema normalization are now handled by Version20260412223000.';
    }

    public function up(Schema $schema): void
    {
        // No-op. Kept for historical continuity.
    }

    public function down(Schema $schema): void
    {
        // No-op. Kept for historical continuity.
    }
}

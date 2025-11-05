<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ensure sessions table exists for Heroku production
 */
final class Version20251105084500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure sessions table exists for production environment';
    }

    public function up(Schema $schema): void
    {
        // Create sessions table if it doesn't exist (safe for both local and Heroku)
        $this->addSql('CREATE TABLE IF NOT EXISTS sessions (
            sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
            sess_data MEDIUMBLOB NOT NULL,
            sess_time INT UNSIGNED NOT NULL,
            sess_lifetime INT UNSIGNED NOT NULL,
            INDEX sess_time_idx (sess_time)
        ) COLLATE utf8mb4_bin, ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Don't drop sessions table on rollback to avoid data loss
        // $this->addSql('DROP TABLE sessions');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250726132833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sessions table for PdoSessionHandler (needed for Heroku CSRF reliability)';
    }

    public function up(Schema $schema): void
    {
        // Create sessions table for storing session data in database
        $this->addSql('CREATE TABLE sessions (
            sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
            sess_data BLOB NOT NULL,
            sess_time INTEGER UNSIGNED NOT NULL,
            sess_lifetime INTEGER UNSIGNED NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin');
    }

    public function down(Schema $schema): void
    {
        // Drop sessions table
        $this->addSql('DROP TABLE sessions');
    }
}

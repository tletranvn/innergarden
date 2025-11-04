<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104142832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sessions table for Heroku compatibility';
    }

    public function up(Schema $schema): void
    {
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
        $this->addSql('DROP TABLE sessions');
    }
}

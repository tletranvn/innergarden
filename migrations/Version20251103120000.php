<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove redundant image metadata columns from article table.
 * Keep only image_public_id. All metadata will be stored in MongoDB.
 */
final class Version20251103120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove image_name, image_size, image_mime_type, image_original_name columns and add image_public_id';
    }

    public function up(Schema $schema): void
    {
        // Add new column for Cloudinary public_id
        $this->addSql('ALTER TABLE article ADD image_public_id VARCHAR(255) DEFAULT NULL');

        // Copy existing imageName to imagePublicId if exists
        $this->addSql('UPDATE article SET image_public_id = image_name WHERE image_name IS NOT NULL');

        // Drop old columns
        $this->addSql('ALTER TABLE article DROP COLUMN image_name');
        $this->addSql('ALTER TABLE article DROP COLUMN image_size');
        $this->addSql('ALTER TABLE article DROP COLUMN image_mime_type');
        $this->addSql('ALTER TABLE article DROP COLUMN image_original_name');
    }

    public function down(Schema $schema): void
    {
        // Restore old columns
        $this->addSql('ALTER TABLE article ADD image_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD image_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD image_mime_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE article ADD image_original_name VARCHAR(255) DEFAULT NULL');

        // Copy imagePublicId back to imageName
        $this->addSql('UPDATE article SET image_name = image_public_id WHERE image_public_id IS NOT NULL');

        // Drop new column
        $this->addSql('ALTER TABLE article DROP COLUMN image_public_id');
    }
}

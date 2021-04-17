<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200707114239 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add site settings for toggling thumbnailing of URLs';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE sites ADD url_images_enabled BOOLEAN DEFAULT TRUE NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE sites DROP url_images_enabled');
    }
}

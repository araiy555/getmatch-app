<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191201131122 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add background image columns for forums';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE forums ADD light_background_image TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE forums ADD dark_background_image TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE forums ADD background_image_mode TEXT NOT NULL DEFAULT \'tile\'');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE forums DROP light_background_image');
        $this->addSql('ALTER TABLE forums DROP dark_background_image');
        $this->addSql('ALTER TABLE forums DROP background_image_mode');
    }
}

<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200530131154 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add toggle for registration CAPTCHA';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE sites ADD registration_captcha_enabled BOOLEAN DEFAULT FALSE NOT NULL');
        // existing sites keep old behaviour (captcha on)
        $this->addSql('UPDATE sites SET registration_captcha_enabled = TRUE');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE sites DROP registration_captcha_enabled');
    }
}

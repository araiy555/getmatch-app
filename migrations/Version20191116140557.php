<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191116140557 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add default theme to site settings';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE sites ADD default_theme_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN sites.default_theme_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE sites ADD CONSTRAINT FK_BC00AA635F4644C9 FOREIGN KEY (default_theme_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX site_default_theme_idx ON sites (default_theme_id)');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE sites DROP CONSTRAINT FK_BC00AA635F4644C9');
        $this->addSql('DROP INDEX site_default_theme_idx');
        $this->addSql('ALTER TABLE sites DROP default_theme_id');
    }
}

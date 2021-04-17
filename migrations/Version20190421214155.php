<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190421214155 extends AbstractMigration {
    public function getDescription(): string {
        return 'Entirely new theme system';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users DROP preferred_theme_id');
        $this->addSql('ALTER TABLE forums DROP theme_id');
        $this->addSql('DROP TABLE theme_revisions');
        $this->addSql('DROP TABLE themes');
        $this->addSql('CREATE TABLE themes (id UUID NOT NULL, config_key TEXT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_154232DE95D1CAA6 ON themes (config_key)');
        $this->addSql('COMMENT ON COLUMN themes.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE users ADD preferred_theme_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN users.preferred_theme_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E94FC448E8 FOREIGN KEY (preferred_theme_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1483A5E94FC448E8 ON users (preferred_theme_id)');
        $this->addSql('ALTER TABLE forums ADD suggested_theme_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN forums.suggested_theme_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE forums ADD CONSTRAINT FK_FE5E5AB889206B2F FOREIGN KEY (suggested_theme_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FE5E5AB889206B2F ON forums (suggested_theme_id)');
    }

    public function down(Schema $schema): void {
        $this->throwIrreversibleMigrationException();
    }
}

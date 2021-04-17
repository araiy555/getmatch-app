<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20170910220650 extends AbstractMigration {
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE theme_revisions (id UUID NOT NULL, theme_id UUID NOT NULL, common_css TEXT DEFAULT NULL, day_css TEXT DEFAULT NULL, night_css TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, modified TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4772F80859027487 ON theme_revisions (theme_id)');
        $this->addSql('COMMENT ON COLUMN theme_revisions.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN theme_revisions.theme_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE theme_revisions ADD CONSTRAINT FK_4772F80859027487 FOREIGN KEY (theme_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('INSERT INTO theme_revisions (id, theme_id, common_css, day_css, night_css, modified) SELECT MD5(RANDOM()::TEXT)::UUID, id, common_css, day_css, night_css, last_modified FROM themes');
        $this->addSql('ALTER TABLE themes DROP common_css');
        $this->addSql('ALTER TABLE themes DROP day_css');
        $this->addSql('ALTER TABLE themes DROP night_css');
        $this->addSql('ALTER TABLE themes DROP append_to_default_style');
        $this->addSql('ALTER TABLE themes DROP last_modified');
    }

    public function down(Schema $schema): void {
        $this->throwIrreversibleMigrationException();
    }
}

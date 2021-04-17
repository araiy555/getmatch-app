<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200712173735 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add support for manually entered CSS themes';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE css_theme_revisions (id UUID NOT NULL, theme_id UUID DEFAULT NULL, timestamp TIMESTAMP(0) WITH TIME ZONE NOT NULL, css TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D0005D9659027487 ON css_theme_revisions (theme_id)');
        $this->addSql('ALTER TABLE css_theme_revisions ADD CONSTRAINT FK_D0005D9659027487 FOREIGN KEY (theme_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN css_theme_revisions.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN css_theme_revisions.theme_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN css_theme_revisions.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('ALTER TABLE themes ADD name TEXT');
        $this->addSql("UPDATE themes SET name = INITCAP(REPLACE(config_key, '-', ' '))");
        $this->addSql('ALTER TABLE themes ALTER name SET NOT NULL');
        $this->addSql("ALTER TABLE themes ADD theme_type TEXT NOT NULL DEFAULT 'bundled'");
        $this->addSql('ALTER TABLE themes ALTER theme_type DROP DEFAULT');
        $this->addSql('ALTER TABLE themes ALTER config_key DROP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX themes_name_idx ON themes (name)');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE css_theme_revisions');
        $this->addSql("DELETE FROM themes WHERE theme_type <> 'bundled'");
        $this->addSql('ALTER TABLE themes DROP name');
        $this->addSql('ALTER TABLE themes DROP theme_type');
        $this->addSql('ALTER TABLE themes ALTER config_key SET NOT NULL');
    }
}

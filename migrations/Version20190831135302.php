<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190831135302 extends AbstractMigration {
    public function getDescription(): string {
        return 'Extend soft-deleted column on comments to visibility column';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE comments RENAME soft_deleted TO visibility');
        $this->addSql("ALTER TABLE comments ALTER visibility TYPE TEXT USING (CASE WHEN body = '' AND visibility = TRUE THEN 'deleted' ELSE 'visible' END)");
        $this->addSql("ALTER TABLE comments ADD CONSTRAINT comments_visibility_constraint CHECK (visibility <> 'deleted' OR body = '')");
        $this->addSql("ALTER TABLE comments ALTER visibility SET DEFAULT 'visible'");
        $this->addSql('CREATE INDEX comments_visibility_idx ON comments (visibility)');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP INDEX comments_visibility_idx');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT comments_visibility_constraint');
        $this->addSql('ALTER TABLE comments RENAME visibility TO soft_deleted');
        $this->addSql("ALTER TABLE comments ALTER soft_deleted TYPE BOOL USING (soft_deleted = 'deleted')");
        $this->addSql('ALTER TABLE comments ALTER soft_deleted SET DEFAULT false');
    }
}

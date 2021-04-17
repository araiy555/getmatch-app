<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200426155958 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add trash site setting, constraints on visibility column';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');
        $this->addSql("ALTER TABLE submissions ALTER visibility SET DEFAULT 'visible'");
        $this->addSql("UPDATE comments SET visibility = 'soft_deleted' WHERE visibility = 'deleted'");
        $this->addSql("UPDATE submissions SET visibility = 'soft_deleted' WHERE visibility = 'deleted'");
        $this->addSql("ALTER TABLE submissions ADD CONSTRAINT submissions_visibility_check CHECK (visibility IN ('visible', 'soft_deleted', 'trashed'))");
        $this->addSql('ALTER TABLE comments RENAME CONSTRAINT comments_visibility_constraint TO comments_visibility_deleted_check');
        $this->addSql("ALTER TABLE comments ADD CONSTRAINT comments_visibility_check CHECK (visibility IN ('visible', 'soft_deleted', 'trashed'))");
        $this->addSql('ALTER TABLE sites ADD trash_enabled BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions ALTER visibility DROP DEFAULT');
        $this->addSql('ALTER TABLE submissions DROP CONSTRAINT submissions_visibility_check');
        $this->addSql('ALTER TABLE comments RENAME CONSTRAINT comments_visibility_deleted_check TO comments_visibility_constraint');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT comments_visibility_check');
        $this->addSql("UPDATE comments SET visibility = 'visible' WHERE visibility = 'trashed'");
        $this->addSql("UPDATE comments SET visibility = 'deleted' WHERE visibility = 'soft_deleted'");
        $this->addSql("UPDATE submissions SET visibility = 'visible' WHERE visibility = 'trashed'");
        $this->addSql("UPDATE submissions SET visibility = 'deleted' WHERE visibility = 'soft_deleted'");
        $this->addSql('ALTER TABLE sites DROP trash_enabled');
    }
}

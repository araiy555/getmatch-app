<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190711235322 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add media type to submissions, add image setting & index';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE INDEX submissions_image_idx ON submissions (image)');
        $this->addSql('ALTER TABLE sites ADD image_uploading_allowed BOOLEAN DEFAULT FALSE NOT NULL');
        $this->addSql('ALTER TABLE sites ALTER image_uploading_allowed DROP DEFAULT');
        $this->addSql('ALTER TABLE submissions ADD media_type TEXT DEFAULT \'url\' NOT NULL');
        $this->addSql('ALTER TABLE submissions ALTER media_type DROP DEFAULT');
        $this->addSql('ALTER TABLE submissions ADD CONSTRAINT submissions_media_constraint CHECK (media_type = \'url\' OR media_type = \'image\' AND image is NOT NULL and url IS NULL)');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX submissions_image_idx');
        $this->addSql('ALTER TABLE sites DROP image_uploading_allowed');
        $this->addSql('ALTER TABLE submissions DROP CONSTRAINT submissions_media_constraint');
        $this->addSql('ALTER TABLE submissions DROP media_type');
    }
}

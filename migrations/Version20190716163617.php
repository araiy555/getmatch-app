<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190716163617 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add configurable site roles for forum creation and image uploads';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE sites ADD forum_create_role TEXT NOT NULL DEFAULT \'ROLE_USER\'');
        $this->addSql('ALTER TABLE sites ALTER forum_create_role DROP DEFAULT');
        $this->addSql('ALTER TABLE sites RENAME image_uploading_allowed TO image_upload_role');
        $this->addSql('ALTER TABLE sites ALTER image_upload_role TYPE TEXT USING (CASE image_upload_role WHEN FALSE THEN \'ROLE_ADMIN\' ELSE \'ROLE_USER\' END)');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE sites DROP forum_create_role');
        $this->addSql('ALTER TABLE sites RENAME image_upload_role TO image_uploading_allowed');
        $this->addSql('ALTER TABLE sites ALTER image_uploading_allowed TYPE BOOLEAN USING (image_uploading_allowed <> \'ROLE_ADMIN\')');
    }
}

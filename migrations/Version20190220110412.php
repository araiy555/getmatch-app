<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190220110412 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add submission column to notifications table';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE notifications ADD submission_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3E1FD4933 FOREIGN KEY (submission_id) REFERENCES submissions (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6000B0D3E1FD4933 ON notifications (submission_id)');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3E1FD4933');
        $this->addSql('DROP INDEX IDX_6000B0D3E1FD4933');
        $this->addSql('ALTER TABLE notifications DROP submission_id');
    }
}

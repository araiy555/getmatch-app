<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190717132933 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add wiki options to site settings';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE sites ADD wiki_edit_role TEXT DEFAULT \'ROLE_USER\' NOT NULL');
        $this->addSql('ALTER TABLE sites ALTER wiki_edit_role DROP DEFAULT');
        $this->addSql('ALTER TABLE sites ADD wiki_enabled BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('ALTER TABLE sites ALTER wiki_enabled DROP DEFAULT');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE sites DROP wiki_edit_role');
        $this->addSql('ALTER TABLE sites DROP wiki_enabled');
    }
}

<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190518103827 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add preference to allow/disallow private messages from anyone';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users ADD allow_private_messages BOOLEAN NOT NULL DEFAULT TRUE');
        $this->addSql('ALTER TABLE users ALTER allow_private_messages DROP DEFAULT');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users DROP allow_private_messages');
    }
}

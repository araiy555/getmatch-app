<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190324150011 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add notification preferences';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users ADD notify_on_reply BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('ALTER TABLE users ADD notify_on_mentions BOOLEAN DEFAULT TRUE NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users DROP notify_on_reply');
        $this->addSql('ALTER TABLE users DROP notify_on_mentions');
    }
}

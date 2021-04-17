<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190503205914 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add timezone field for users';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users ADD timezone TEXT');
        $this->addSql('UPDATE users SET timezone = ?', [date_default_timezone_get()]);
        $this->addSql('ALTER TABLE users ALTER timezone SET NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users DROP timezone');
    }
}

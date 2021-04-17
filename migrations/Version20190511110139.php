<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190511110139 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add visibility field to submissions';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions ADD visibility TEXT NOT NULL DEFAULT \'visible\'');
        $this->addSql('ALTER TABLE submissions ALTER visibility DROP DEFAULT');
        $this->addSql('CREATE INDEX submissions_visibility_idx ON submissions (visibility)');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions DROP visibility');
    }
}

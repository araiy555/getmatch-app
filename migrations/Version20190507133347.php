<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190507133347 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add column for last activity';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions ADD last_active TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('UPDATE submissions s SET last_active = (SELECT COALESCE(MAX(c.timestamp), s.timestamp) FROM comments c WHERE c.submission_id = s.id)');
        $this->addSql('ALTER TABLE submissions ALTER last_active SET NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions DROP last_active');
    }
}

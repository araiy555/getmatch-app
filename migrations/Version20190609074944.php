<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190609074944 extends AbstractMigration {
    public function getDescription(): string {
        return 'Remove webhooks';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE forum_webhooks');
    }

    public function down(Schema $schema): void {
        $this->throwIrreversibleMigrationException();
    }
}

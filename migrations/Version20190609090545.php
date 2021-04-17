<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190609090545 extends AbstractMigration {
    public function getDescription(): string {
        return 'Fix deletion of forums when referenced in hidden_forums table';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE hidden_forums DROP CONSTRAINT FK_9FEA4CBF29CCBAD0');
        $this->addSql('ALTER TABLE hidden_forums ADD CONSTRAINT FK_9FEA4CBF29CCBAD0 FOREIGN KEY (forum_id) REFERENCES forums (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE hidden_forums DROP CONSTRAINT fk_9fea4cbf29ccbad0');
        $this->addSql('ALTER TABLE hidden_forums ADD CONSTRAINT fk_9fea4cbf29ccbad0 FOREIGN KEY (forum_id) REFERENCES forums (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}

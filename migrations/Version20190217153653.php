<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190217153653 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add hidden forum table';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE hidden_forums (user_id BIGINT NOT NULL, forum_id BIGINT NOT NULL, PRIMARY KEY(user_id, forum_id))');
        $this->addSql('CREATE INDEX IDX_9FEA4CBFA76ED395 ON hidden_forums (user_id)');
        $this->addSql('CREATE INDEX IDX_9FEA4CBF29CCBAD0 ON hidden_forums (forum_id)');
        $this->addSql('ALTER TABLE hidden_forums ADD CONSTRAINT FK_9FEA4CBFA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE hidden_forums ADD CONSTRAINT FK_9FEA4CBF29CCBAD0 FOREIGN KEY (forum_id) REFERENCES forums (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE hidden_forums');
    }
}

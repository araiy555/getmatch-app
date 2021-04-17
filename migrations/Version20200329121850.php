<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200329121850 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add bad phrases table';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE bad_phrases (id UUID NOT NULL, timestamp TIMESTAMP(0) WITH TIME ZONE NOT NULL, phrase TEXT NOT NULL, phrase_type TEXT NOT NULL DEFAULT \'text\', PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX bad_phrases_phrase_type_idx ON bad_phrases (phrase, phrase_type)');
        $this->addSql("ALTER TABLE bad_phrases ADD CONSTRAINT bad_phrases_type_constraint CHECK (phrase_type IN ('text', 'regex'))");
        $this->addSql('COMMENT ON COLUMN bad_phrases.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN bad_phrases.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE bad_phrases');
    }
}

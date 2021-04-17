<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20170912180315 extends AbstractMigration {
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE wiki_revisions_id_seq');
        $this->addSql('ALTER TABLE wiki_pages DROP CONSTRAINT fk_8ffedcf9a32ed756');
        $this->addSql('DROP INDEX idx_8ffedcf9a32ed756');
        $this->addSql('ALTER TABLE wiki_pages DROP current_revision_id');
        $this->addSql('ALTER TABLE wiki_revisions ALTER id TYPE UUID USING (MD5(RANDOM()::TEXT)::UUID)');
    }

    public function down(Schema $schema): void {
        $this->throwIrreversibleMigrationException();
    }
}

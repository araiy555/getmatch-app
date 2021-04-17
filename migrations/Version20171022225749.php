<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20171022225749 extends AbstractMigration {
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE forum_log_entries ADD ban_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN forum_log_entries.ban_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE forum_log_entries ADD CONSTRAINT FK_130108F01255CD1D FOREIGN KEY (ban_id) REFERENCES forum_bans (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_130108F01255CD1D ON forum_log_entries (ban_id)');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE forum_log_entries DROP CONSTRAINT FK_130108F01255CD1D');
        $this->addSql('DROP INDEX IDX_130108F01255CD1D');
        $this->addSql('ALTER TABLE forum_log_entries DROP ban_id');
    }
}

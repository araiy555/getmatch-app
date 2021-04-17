<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20171126073605 extends AbstractMigration {
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        /* @noinspection SpellCheckingInspection */
        $this->addSql('UPDATE forum_log_entries SET action_type = \'submission_lock\' WHERE action_type = \'submssion_lock\'');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        /* @noinspection SpellCheckingInspection */
        $this->addSql('UPDATE forum_log_entries SET action_type = \'submssion_lock\' WHERE action_type = \'submission_lock\'');
    }
}

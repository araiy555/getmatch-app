<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210107164021 extends AbstractMigration
{
    public function getDescription(): string {
        return 'Add site & user settings for submission link destinations';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE sites ADD submission_link_destination TEXT DEFAULT \'url\' NOT NULL');
        $this->addSql("ALTER TABLE sites ADD CONSTRAINT sites_submission_link_destination_check CHECK (submission_link_destination IN ('submission', 'url'))");
        $this->addSql('ALTER TABLE users ADD submission_link_destination TEXT DEFAULT \'url\' NOT NULL');
        $this->addSql("ALTER TABLE users ADD CONSTRAINT users_submission_link_destination_check CHECK (submission_link_destination IN ('submission', 'url'))");
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE sites DROP submission_link_destination');
        $this->addSql('ALTER TABLE users DROP submission_link_destination');
    }
}

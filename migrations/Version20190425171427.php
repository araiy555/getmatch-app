<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190425171427 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add preference for default submission sort mode';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users ADD front_page_sort_mode TEXT NOT NULL DEFAULT \'hot\'');
        $this->addSql('ALTER TABLE users ALTER front_page_sort_mode DROP DEFAULT');
        $this->addSql('ALTER TABLE users ALTER front_page DROP DEFAULT');
        $this->addSql('UPDATE users SET front_page = \'subscribed\' WHERE front_page = \'default\'');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users DROP front_page_sort_mode');
        $this->addSql('ALTER TABLE users ALTER front_page SET DEFAULT \'default\'');
    }
}

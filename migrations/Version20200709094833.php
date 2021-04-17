<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200709094833 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add language fields to submissions and comments';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE comments ADD language TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE submissions ADD language TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE submissions DROP language');
        $this->addSql('ALTER TABLE comments DROP language');
    }
}

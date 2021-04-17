<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190706143243 extends AbstractMigration {
    public function getDescription(): string {
        return 'Store some site settings in database';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE sites (id UUID NOT NULL, site_name TEXT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('COMMENT ON COLUMN sites.id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP TABLE sites');
    }
}

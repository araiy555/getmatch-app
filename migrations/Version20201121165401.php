<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201121165401 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add SHA256 field for images';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE images ADD sha256 BYTEA DEFAULT NULL');
        $this->addSql('UPDATE images SET sha256 = DECODE(SUBSTRING(file_name, 1, 64), \'hex\')');
        $this->addSql('ALTER TABLE images ALTER sha256 SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX images_sha256_idx ON images (sha256)');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP INDEX images_sha256_idx');
        $this->addSql('ALTER TABLE images DROP sha256');
    }
}

<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201012165235 extends AbstractMigration
{
    public function getDescription(): string {
        return 'Add full-width display mode';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE users ADD full_width_display_enabled BOOLEAN DEFAULT FALSE NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE users DROP full_width_display_enabled');
    }
}

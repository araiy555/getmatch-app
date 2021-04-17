<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190525163849 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add index to comments.timestamp to speed up user page';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE INDEX comments_timestamp_idx ON comments (timestamp)');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP INDEX comments_timestamp_idx');
    }
}

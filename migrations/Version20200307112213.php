<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200307112213 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add auto option for night mode';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql("ALTER TABLE users ALTER night_mode TYPE TEXT USING (CASE night_mode WHEN FALSE THEN 'light' ELSE 'dark' END)");
        $this->addSql("ALTER TABLE users ALTER night_mode SET DEFAULT 'auto'");
        $this->addSql("ALTER TABLE users ADD CONSTRAINT users_night_mode_constraint CHECK (night_mode = 'light' OR night_mode = 'dark' OR night_mode = 'auto')");
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users DROP CONSTRAINT users_night_mode_constraint');
        $this->addSql('ALTER TABLE users ALTER night_mode DROP DEFAULT');
        $this->addSql("ALTER TABLE users ALTER night_mode TYPE BOOLEAN USING (night_mode = 'dark')");
    }
}

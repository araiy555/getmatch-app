<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20170102011412 extends AbstractMigration {
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users ADD canonical_username TEXT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E96A9AF538 ON users (canonical_username)');
        $this->addSql('UPDATE users SET username = username || \'-\' || id WHERE LOWER(username) IN (SELECT lower_username FROM (SELECT LOWER(username) AS lower_username, COUNT(*) AS count FROM users GROUP BY LOWER(username)) AS q WHERE count > 1)');
        $this->addSql('UPDATE users SET canonical_username = LOWER(username)');
        $this->addSql('ALTER TABLE users ALTER COLUMN canonical_username SET NOT NULL');

        $this->addSql('ALTER TABLE users ADD canonical_email TEXT');
        $this->addSql('UPDATE users SET canonical_email = LOWER(email)');
        $this->addSql('ALTER TABLE users ALTER COLUMN canonical_email SET NOT NULL');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP INDEX UNIQ_1483A5E96A9AF538');
        $this->addSql('ALTER TABLE users DROP canonical_username');
        $this->addSql('ALTER TABLE users DROP canonical_email');
        $this->addSql('UPDATE users SET username = REGEXP_REPLACE(username, \'-.*\', \'\')');
    }
}

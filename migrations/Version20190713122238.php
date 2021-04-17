<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190713122238 extends AbstractMigration {
    public function getDescription(): string {
        return 'Use strings instead of ints for user flags';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions ALTER user_flag DROP DEFAULT');
        $this->addSql('ALTER TABLE submissions ALTER user_flag TYPE TEXT USING (CASE user_flag WHEN 0 THEN \'none\' WHEN 1 THEN \'moderator\' WHEN 2 THEN \'admin\' END)');
        $this->addSql('ALTER TABLE comments ALTER user_flag DROP DEFAULT');
        $this->addSql('ALTER TABLE comments ALTER user_flag TYPE TEXT USING (CASE user_flag WHEN 0 THEN \'none\' WHEN 1 THEN \'moderator\' WHEN 2 THEN \'admin\' END)');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE comments ALTER user_flag TYPE SMALLINT USING (CASE user_flag WHEN \'none\' THEN 0 WHEN \'moderator\' THEN 1 WHEN \'admin\' THEN 2 END)');
        $this->addSql('ALTER TABLE comments ALTER user_flag SET DEFAULT 0');
        $this->addSql('ALTER TABLE submissions ALTER user_flag TYPE SMALLINT USING (CASE user_flag WHEN \'none\' THEN 0 WHEN \'moderator\' THEN 1 WHEN \'admin\' THEN 2 END)');
        $this->addSql('ALTER TABLE submissions ALTER user_flag SET DEFAULT 0');
    }
}

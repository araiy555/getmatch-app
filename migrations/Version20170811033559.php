<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20170811033559 extends AbstractMigration {
    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE forum_subscriptions_id_seq CASCADE');
        // we don't need a CSPRNG here, so md5s of random floats are fine.
        // uuid-ossp is not viable since it requires special permissions.
        $this->addSql('ALTER TABLE forum_subscriptions ALTER COLUMN id SET DATA TYPE UUID USING (MD5(RANDOM()::TEXT)::UUID)');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE forum_subscriptions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE forum_subscriptions ALTER COLUMN id SET DATA TYPE BIGINT USING (nextval(\'forum_subscriptions_id_seq\'))');
    }
}

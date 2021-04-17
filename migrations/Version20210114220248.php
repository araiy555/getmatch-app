<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210114220248 extends AbstractMigration {
    public function getDescription(): string {
        return 'Change notification ID type to UUID, add timestamp';
    }

    public function up(Schema $schema): void {
        $this->addSql('ALTER TABLE notifications ADD timestamp TIMESTAMP(0) WITH TIME ZONE NOT NULL DEFAULT NOW()');
        $this->addSql('UPDATE notifications SET timestamp = TO_TIMESTAMP(?)', [time()]);
        $this->addSql('ALTER TABLE notifications ALTER timestamp DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN notifications.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('DROP SEQUENCE notifications_id_seq CASCADE');
        $this->addSql("ALTER TABLE notifications ALTER id TYPE UUID USING ((LPAD(TO_HEX(id), 8, '0') || SUBSTRING(MD5(RANDOM()::TEXT), 1, 24))::UUID)");
        $this->addSql('COMMENT ON COLUMN notifications.id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void {
        $this->addSql('CREATE SEQUENCE notifications_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE tmp_notif_id_map (id UUID NOT NULL, id2 INTEGER NOT NULL)');
        $this->addSql("INSERT INTO tmp_notif_id_map (id, id2) SELECT id, NEXTVAL('notifications_id_seq') FROM notifications ORDER BY timestamp ASC, id ASC");
        $this->addSql('ALTER TABLE notifications ADD id2 INTEGER DEFAULT 0');
        $this->addSql('UPDATE notifications n SET id2 = (SELECT m.id2 FROM tmp_notif_id_map m WHERE m.id = n.id)');
        $this->addSql("ALTER TABLE notifications ALTER id TYPE INT USING (id2)");
        $this->addSql('ALTER TABLE notifications DROP id2');
        $this->addSql('DROP TABLE tmp_notif_id_map');
        $this->addSql('ALTER TABLE notifications DROP timestamp');
        $this->addSql('ALTER TABLE notifications ALTER id DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN notifications.id IS NULL');
    }
}

<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190331135638 extends AbstractMigration {
    public function getDescription(): string {
        return 'Restructure messages to allow for group convos/deletion of OP without deletion of replies';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        // create map
        $this->addSql('CREATE TABLE temp_message_map (new_id UUID NOT NULL, old_thread_id BIGINT, old_reply_id BIGINT)');
        $this->addSql('INSERT INTO temp_message_map (new_id, old_thread_id) SELECT MD5(RANDOM()::TEXT)::UUID, id FROM message_threads');
        $this->addSql('INSERT INTO temp_message_map (new_id, old_reply_id) SELECT MD5(RANDOM()::TEXT)::UUID, id FROM message_replies');

        // new message threads
        $this->addSql('ALTER TABLE message_threads RENAME TO old_message_threads');
        $this->addSql('CREATE TABLE message_threads (id BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO message_threads (id) SELECT id FROM old_message_threads');

        // message thread participants
        $this->addSql('CREATE TABLE message_thread_participants (message_thread_id BIGINT NOT NULL, user_id BIGINT NOT NULL, PRIMARY KEY(message_thread_id, user_id))');
        $this->addSql('CREATE INDEX IDX_F2DE92908829462F ON message_thread_participants (message_thread_id)');
        $this->addSql('CREATE INDEX IDX_F2DE9290A76ED395 ON message_thread_participants (user_id)');
        $this->addSql('ALTER TABLE message_thread_participants ADD CONSTRAINT FK_F2DE92908829462F FOREIGN KEY (message_thread_id) REFERENCES message_threads (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message_thread_participants ADD CONSTRAINT FK_F2DE9290A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('INSERT INTO message_thread_participants (message_thread_id, user_id) SELECT id, sender_id FROM old_message_threads WHERE sender_id <> receiver_id');
        $this->addSql('INSERT INTO message_thread_participants (message_thread_id, user_id) SELECT id, receiver_id FROM old_message_threads');

        // new message table, insert messages
        $this->addSql('CREATE TABLE messages (id UUID NOT NULL, sender_id BIGINT NOT NULL, thread_id BIGINT NOT NULL, body TEXT NOT NULL, timestamp TIMESTAMP(0) WITH TIME ZONE NOT NULL, ip INET, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DB021E96E2904019 ON messages (thread_id)');
        $this->addSql('CREATE INDEX IDX_DB021E96F624B39D ON messages (sender_id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E2904019 FOREIGN KEY (thread_id) REFERENCES message_threads (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96F624B39D FOREIGN KEY (sender_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN messages.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN messages.ip IS \'(DC2Type:inet)\'');
        $this->addSql('INSERT INTO messages (id, sender_id, thread_id, body, timestamp, ip) SELECT new_id, sender_id, id, \'# \' || title || E\'\n\n\' || body, timestamp, ip FROM old_message_threads mt JOIN temp_message_map m ON mt.id = m.old_thread_id');
        $this->addSql('INSERT INTO messages (id, sender_id, thread_id, body, timestamp, ip) SELECT new_id, sender_id, thread_id, body, timestamp, ip FROM message_replies mr JOIN temp_message_map m ON mr.id = m.old_reply_id');

        // notifications
        $this->addSql('ALTER TABLE notifications ADD message_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN notifications.message_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3537A1329 FOREIGN KEY (message_id) REFERENCES messages (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6000B0D3537A1329 ON notifications (message_id)');
        $this->addSql('UPDATE notifications SET notification_type = \'message\', message_id = new_id FROM temp_message_map WHERE thread_id = old_thread_id OR reply_id = old_reply_id');

        // cleanup
        $this->addSql('ALTER TABLE notifications DROP thread_id');
        $this->addSql('ALTER TABLE notifications DROP reply_id');
        $this->addSql('DROP SEQUENCE message_replies_id_seq CASCADE');
        $this->addSql('DROP TABLE message_replies');
        $this->addSql('DROP TABLE old_message_threads');
        $this->addSql('DROP TABLE temp_message_map');
    }

    public function down(Schema $schema): void {
        $this->throwIrreversibleMigrationException();
    }
}

<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200222122754 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add comment hints to make date/times immutable in PHP';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('COMMENT ON COLUMN bans.expiry_date IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN bans.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN comment_votes.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN comments.edited_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN comments.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN forum_bans.expires_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN forum_bans.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN forum_log_entries.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN forum_subscriptions.subscribed_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN forums.created IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messages.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN moderators.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN submission_votes.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN submissions.edited_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN submissions.last_active IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN submissions.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_bans.expires_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_bans.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_blocks.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.created IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.last_seen IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN wiki_revisions.timestamp IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('COMMENT ON COLUMN bans."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN bans.expiry_date IS NULL');
        $this->addSql('COMMENT ON COLUMN comment_votes."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN comments."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN comments.edited_at IS NULL');
        $this->addSql('COMMENT ON COLUMN forum_bans."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN forum_bans.expires_at IS NULL');
        $this->addSql('COMMENT ON COLUMN forum_log_entries."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN forum_subscriptions.subscribed_at IS NULL');
        $this->addSql('COMMENT ON COLUMN forums.created IS NULL');
        $this->addSql('COMMENT ON COLUMN messages."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN moderators."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN submission_votes."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN submissions."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN submissions.edited_at IS NULL');
        $this->addSql('COMMENT ON COLUMN submissions.last_active IS NULL');
        $this->addSql('COMMENT ON COLUMN user_bans."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN user_bans.expires_at IS NULL');
        $this->addSql('COMMENT ON COLUMN user_blocks."timestamp" IS NULL');
        $this->addSql('COMMENT ON COLUMN users.created IS NULL');
        $this->addSql('COMMENT ON COLUMN users.last_seen IS NULL');
        $this->addSql('COMMENT ON COLUMN wiki_revisions."timestamp" IS NULL');
    }
}

<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190509152528 extends AbstractMigration {
    public function getDescription(): string {
        return 'Optimise submission and comment retrieval';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions ADD comment_count INT');
        $this->addSql('ALTER TABLE submissions ADD net_score INT');
        $this->addSql('ALTER TABLE comments ADD net_score INT');
        $this->addSql('UPDATE submissions s SET comment_count = (SELECT COUNT(*) FROM comments c WHERE c.submission_id = s.id)');
        $this->addSql('UPDATE submissions s SET net_score = (SELECT COUNT(*) FILTER (WHERE upvote = TRUE) - COUNT(*) FILTER (WHERE upvote = FALSE) FROM submission_votes sv WHERE sv.submission_id = s.id)');
        $this->addSql('UPDATE comments c SET net_score = (SELECT COUNT(*) FILTER (WHERE upvote = TRUE) - COUNT(*) FILTER (WHERE upvote = FALSE) FROM comment_votes cv WHERE cv.comment_id = c.id)');
        $this->addSql('ALTER TABLE submissions ALTER comment_count SET NOT NULL');
        $this->addSql('ALTER TABLE submissions ALTER net_score SET NOT NULL');
        $this->addSql('ALTER TABLE comments ALTER net_score SET NOT NULL');
        $this->addSql('CREATE INDEX submissions_timestamp_idx ON submissions (timestamp)');
        $this->addSql('CREATE INDEX submissions_last_active_id_idx ON submissions (last_active, id)');
        $this->addSql('CREATE INDEX submissions_net_score_id_idx ON submissions (net_score, id)');
        $this->addSql('CREATE INDEX submissions_comment_count_id_idx ON submissions (comment_count, id)');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions DROP comment_count');
        $this->addSql('ALTER TABLE submissions DROP net_score');
        $this->addSql('ALTER TABLE comments DROP net_score');
        $this->addSql('DROP INDEX submissions_timestamp_idx');
        $this->addSql('DROP INDEX submissions_last_active_id_idx');
    }
}

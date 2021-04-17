<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190322210445 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add search document column for submissions and comments';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE submissions ADD search_doc TSVECTOR');
        $this->addSql('COMMENT ON COLUMN submissions.search_doc IS \'(DC2Type:tsvector)\'');
        $this->addSql('ALTER TABLE comments ADD search_doc TSVECTOR');
        $this->addSql('COMMENT ON COLUMN comments.search_doc IS \'(DC2Type:tsvector)\'');
        $this->addSql(<<<'EOSQL'
CREATE FUNCTION submissions_search_trigger() RETURNS trigger AS $$
begin
    new.search_doc :=
        setweight(to_tsvector(new.title), 'A') ||
        setweight(to_tsvector(COALESCE(new.url, '')), 'B') ||
        setweight(to_tsvector(COALESCE(new.body, '')), 'D');

    return new;
end
$$ LANGUAGE plpgsql
EOSQL
        );
        $this->addSql('CREATE TRIGGER submissions_search_update BEFORE INSERT OR UPDATE ON submissions FOR EACH ROW EXECUTE PROCEDURE submissions_search_trigger()');
        $this->addSql('CREATE TRIGGER comments_search_update BEFORE INSERT OR UPDATE ON comments FOR EACH ROW EXECUTE PROCEDURE tsvector_update_trigger(search_doc, \'pg_catalog.english\', body)');
        $this->addSql('CREATE INDEX submissions_search_idx ON submissions USING GIN (search_doc)');
        $this->addSql('CREATE INDEX comments_search_idx ON comments USING GIN (search_doc)');
        $this->addSql('UPDATE submissions SET id = id');
        $this->addSql('UPDATE comments SET id = id');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP FUNCTION submissions_search_trigger CASCADE');
        $this->addSql('ALTER TABLE submissions DROP search_doc');
        $this->addSql('DROP TRIGGER comments_search_update ON comments');
        $this->addSql('ALTER TABLE comments DROP search_doc');
    }
}

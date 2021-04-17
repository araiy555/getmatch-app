<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201003180645 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add forum tags support';
    }

    public function up(Schema $schema): void {
        $this->addSql('CREATE TABLE forum_tags (id UUID NOT NULL, name TEXT NOT NULL, normalized_name TEXT NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN forum_tags.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX forum_tag_name_idx ON forum_tags (name)');
        $this->addSql('CREATE UNIQUE INDEX forum_tag_normalized_name_idx ON forum_tags (normalized_name)');

        $this->addSql('CREATE TABLE forums_tags (forum_id BIGINT NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(forum_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_5A8387A729CCBAD0 ON forums_tags (forum_id)');
        $this->addSql('CREATE INDEX IDX_5A8387A7BAD26311 ON forums_tags (tag_id)');
        $this->addSql('ALTER TABLE forums_tags ADD CONSTRAINT FK_5878875329CCBAD0 FOREIGN KEY (forum_id) REFERENCES forums (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE forums_tags ADD CONSTRAINT FK_58788753BAD26311 FOREIGN KEY (tag_id) REFERENCES forum_tags (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql("INSERT INTO forum_tags (id, name, normalized_name, description) SELECT MD5(id::TEXT)::UUID, name, normalized_name, NULLIF((CASE WHEN title <> name THEN '## ' || title || E'\\n\\n' ELSE '' END) || (CASE WHEN description <> name THEN description ELSE '' END) || (CASE WHEN sidebar <> description THEN E'\\n\\n' || sidebar ELSE '' END), '') FROM forum_categories");
        $this->addSql('INSERT INTO forums_tags (forum_id, tag_id) SELECT id, MD5(category_id::TEXT)::UUID FROM forums WHERE category_id IS NOT NULL');
        $this->addSql('COMMENT ON COLUMN forums_tags.tag_id IS \'(DC2Type:uuid)\'');

        $this->addSql('DROP INDEX idx_fe5e5ab812469de2');
        $this->addSql('ALTER TABLE forums DROP CONSTRAINT fk_fe5e5ab812469de2');
        $this->addSql('ALTER TABLE forums DROP category_id');
        $this->addSql('DROP SEQUENCE forum_categories_id_seq CASCADE');
        $this->addSql('DROP TABLE forum_categories');
    }

    public function down(Schema $schema): void {
        $this->addSql('CREATE SEQUENCE forum_categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE forum_categories (id BIGINT NOT NULL, title TEXT NOT NULL, name TEXT NOT NULL, normalized_name TEXT NOT NULL, description TEXT NOT NULL, sidebar TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX forum_categories_normalized_name_idx ON forum_categories (normalized_name)');
        $this->addSql('CREATE UNIQUE INDEX forum_categories_name_idx ON forum_categories (name)');
        $this->addSql('ALTER TABLE forums ADD category_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE forums ADD CONSTRAINT fk_fe5e5ab812469de2 FOREIGN KEY (category_id) REFERENCES forum_categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fe5e5ab812469de2 ON forums (category_id)');

        $this->addSql("INSERT INTO forum_categories (id, name, normalized_name, title, description, sidebar) SELECT nextval('forum_categories_id_seq'), name, normalized_name, name, name, COALESCE(description, name) FROM forum_tags");
        $this->addSql('UPDATE forums f SET category_id = (SELECT fc.id FROM forum_categories fc JOIN forum_tags ft ON fc.name = ft.name JOIN forums_tags fsts ON fsts.tag_id = ft.id WHERE fsts.forum_id = f.id LIMIT 1)');

        $this->addSql('DROP TABLE forums_tags');
        $this->addSql('DROP TABLE forum_tags');
    }
}

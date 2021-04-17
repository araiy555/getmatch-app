<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190422163452 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add SET NULL to theme join columns/fix doctrine type hints';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E94FC448E8');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E94FC448E8 FOREIGN KEY (preferred_theme_id) REFERENCES themes (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE forums DROP CONSTRAINT FK_FE5E5AB889206B2F');
        $this->addSql('ALTER TABLE forums ADD CONSTRAINT FK_FE5E5AB889206B2F FOREIGN KEY (suggested_theme_id) REFERENCES themes (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN forum_subscriptions.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN moderators.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN wiki_revisions.id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE forums DROP CONSTRAINT fk_fe5e5ab889206b2f');
        $this->addSql('ALTER TABLE forums ADD CONSTRAINT fk_fe5e5ab889206b2f FOREIGN KEY (suggested_theme_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT fk_1483a5e94fc448e8');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT fk_1483a5e94fc448e8 FOREIGN KEY (preferred_theme_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN forum_subscriptions.id IS NULL');
        $this->addSql('COMMENT ON COLUMN moderators.id IS NULL');
        $this->addSql('COMMENT ON COLUMN wiki_revisions.id IS NULL');
    }
}

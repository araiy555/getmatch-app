<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191210231854 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add image table';
    }

    public function up(Schema $schema): void {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE images (id UUID NOT NULL, file_name TEXT NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX images_file_name_idx ON images (file_name)');
        $this->addSql('COMMENT ON COLUMN images.id IS \'(DC2Type:uuid)\'');
        $this->addSql('INSERT INTO images (id, file_name) SELECT DISTINCT MD5(image)::UUID, image FROM submissions WHERE image is NOT NULL');
        $this->addSql('INSERT INTO images (id, file_name) SELECT DISTINCT MD5(light_background_image)::UUID, light_background_image FROM forums WHERE light_background_image is NOT NULL AND NOT EXISTS (SELECT 1 FROM images WHERE file_name = light_background_image)');
        $this->addSql('INSERT INTO images (id, file_name) SELECT DISTINCT MD5(dark_background_image)::UUID, dark_background_image FROM forums WHERE dark_background_image is NOT NULL AND NOT EXISTS (SELECT 1 FROM images WHERE file_name = dark_background_image)');
        $this->addSql('ALTER TABLE forums ALTER light_background_image TYPE UUID USING (MD5(light_background_image)::UUID)');
        $this->addSql('ALTER TABLE forums ALTER dark_background_image TYPE UUID USING (MD5(dark_background_image)::UUID)');
        $this->addSql('ALTER TABLE submissions ALTER image TYPE UUID USING (MD5(image)::UUID)');
        $this->addSql('ALTER TABLE forums RENAME light_background_image to light_background_image_id');
        $this->addSql('ALTER TABLE forums RENAME dark_background_image to dark_background_image_id');
        $this->addSql('ALTER TABLE submissions RENAME image to image_id');
        $this->addSql('COMMENT ON COLUMN forums.light_background_image_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN forums.dark_background_image_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN submissions.image_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE forums ADD CONSTRAINT FK_FE5E5AB89B1BA11B FOREIGN KEY (light_background_image_id) REFERENCES images (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE forums ADD CONSTRAINT FK_FE5E5AB8E351E462 FOREIGN KEY (dark_background_image_id) REFERENCES images (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE submissions ADD CONSTRAINT FK_3F6169F73DA5256D FOREIGN KEY (image_id) REFERENCES images (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX submissions_image_idx');
        $this->addSql('CREATE INDEX forums_light_background_image_id_idx ON forums (light_background_image_id)');
        $this->addSql('CREATE INDEX forums_dark_background_image_id_idx ON forums (dark_background_image_id)');
        $this->addSql('CREATE INDEX submissions_image_id_idx ON submissions (image_id)');
    }

    public function down(Schema $schema): void {
        $this->addSql('ALTER TABLE forums ADD temp_light_image TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE forums ADD temp_dark_image TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE submissions ADD temp_image TEXT DEFAULT NULL');
        $this->addSql('UPDATE forums f SET temp_light_image = (SELECT file_name FROM images i WHERE i.id = f.light_background_image_id) WHERE light_background_image_id IS NOT NULL');
        $this->addSql('UPDATE forums f SET temp_dark_image = (SELECT file_name FROM images i WHERE i.id = f.dark_background_image_id) WHERE dark_background_image_id IS NOT NULL');
        $this->addSql('UPDATE submissions s SET temp_image = (SELECT file_name FROM images i WHERE i.id = s.image_id) WHERE image_id IS NOT NULL');
        $this->addSql('ALTER TABLE forums DROP CONSTRAINT FK_FE5E5AB89B1BA11B');
        $this->addSql('ALTER TABLE forums DROP CONSTRAINT FK_FE5E5AB8E351E462');
        $this->addSql('ALTER TABLE submissions DROP CONSTRAINT FK_3F6169F73DA5256D');
        $this->addSql('ALTER TABLE forums ALTER light_background_image_id TYPE TEXT USING (temp_light_image)');
        $this->addSql('ALTER TABLE forums ALTER dark_background_image_id TYPE TEXT USING (temp_dark_image)');
        $this->addSql('ALTER TABLE submissions ALTER image_id TYPE TEXT USING (temp_image)');
        $this->addSql('ALTER TABLE forums RENAME light_background_image_id TO light_background_image');
        $this->addSql('ALTER TABLE forums RENAME dark_background_image_id TO dark_background_image');
        $this->addSql('ALTER TABLE submissions RENAME image_id TO image');
        $this->addSql('ALTER TABLE forums DROP temp_light_image');
        $this->addSql('ALTER TABLE forums DROP temp_dark_image');
        $this->addSql('ALTER TABLE submissions DROP temp_image');
        $this->addSql('DROP TABLE images');
        $this->addSql('DROP INDEX forums_light_background_image_id_idx');
        $this->addSql('DROP INDEX forums_dark_background_image_id_idx');
        $this->addSql('DROP INDEX submissions_image_id_idx');
        $this->addSql('CREATE INDEX submissions_image_idx ON submissions (image)');
    }
}

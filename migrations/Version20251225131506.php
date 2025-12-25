<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251225131506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INTEGER DEFAULT NULL, updated_at DATETIME DEFAULT NULL, caption VARCHAR(255) DEFAULT NULL, position INTEGER DEFAULT 0 NOT NULL, article_id INTEGER NOT NULL, CONSTRAINT FK_B28A764E7294869C FOREIGN KEY (article_id) REFERENCES symfony_demo_article (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B28A764E7294869C ON article_image (article_id)');
        $this->addSql('CREATE TABLE article_video (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, url VARCHAR(255) NOT NULL, caption VARCHAR(255) DEFAULT NULL, position INTEGER DEFAULT 0 NOT NULL, article_id INTEGER NOT NULL, CONSTRAINT FK_B70A83D7294869C FOREIGN KEY (article_id) REFERENCES symfony_demo_article (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B70A83D7294869C ON article_video (article_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE article_image');
        $this->addSql('DROP TABLE article_video');
    }
}

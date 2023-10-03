<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231003105524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE site_server (site_id INT NOT NULL, server_id INT NOT NULL, INDEX IDX_2AC37BA9F6BD1646 (site_id), INDEX IDX_2AC37BA91844E6B7 (server_id), PRIMARY KEY(site_id, server_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE site_server ADD CONSTRAINT FK_2AC37BA9F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_server ADD CONSTRAINT FK_2AC37BA91844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site_server DROP FOREIGN KEY FK_2AC37BA9F6BD1646');
        $this->addSql('ALTER TABLE site_server DROP FOREIGN KEY FK_2AC37BA91844E6B7');
        $this->addSql('DROP TABLE site_server');
    }
}

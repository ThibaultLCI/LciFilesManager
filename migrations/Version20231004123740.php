<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231004123740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE site_folder (site_id INT NOT NULL, folder_id INT NOT NULL, INDEX IDX_9C0CA792F6BD1646 (site_id), INDEX IDX_9C0CA792162CB942 (folder_id), PRIMARY KEY(site_id, folder_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE site_folder ADD CONSTRAINT FK_9C0CA792F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_folder ADD CONSTRAINT FK_9C0CA792162CB942 FOREIGN KEY (folder_id) REFERENCES folder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE folder ADD server_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE folder ADD CONSTRAINT FK_ECA209CD1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id)');
        $this->addSql('CREATE INDEX IDX_ECA209CD1844E6B7 ON folder (server_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site_folder DROP FOREIGN KEY FK_9C0CA792F6BD1646');
        $this->addSql('ALTER TABLE site_folder DROP FOREIGN KEY FK_9C0CA792162CB942');
        $this->addSql('DROP TABLE site_folder');
        $this->addSql('ALTER TABLE folder DROP FOREIGN KEY FK_ECA209CD1844E6B7');
        $this->addSql('DROP INDEX IDX_ECA209CD1844E6B7 ON folder');
        $this->addSql('ALTER TABLE folder DROP server_id');
    }
}

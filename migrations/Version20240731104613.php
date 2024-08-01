<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240731104613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE consultation (id INT AUTO_INCREMENT NOT NULL, projet_id INT DEFAULT NULL, nom_entreprise VARCHAR(255) NOT NULL, ville_entreprise VARCHAR(255) NOT NULL, departement_entreprise VARCHAR(255) NOT NULL, nom_consultation VARCHAR(255) NOT NULL, annee_creation_consultation VARCHAR(255) NOT NULL, id_consultation VARCHAR(255) NOT NULL, folder_name VARCHAR(255) NOT NULL, old_folder_name VARCHAR(255) DEFAULT NULL, INDEX IDX_964685A6C18272 (projet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE folder (id INT AUTO_INCREMENT NOT NULL, server_id INT DEFAULT NULL, path VARCHAR(255) NOT NULL, INDEX IDX_ECA209CD1844E6B7 (server_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE projet (id INT AUTO_INCREMENT NOT NULL, nom_site VARCHAR(255) NOT NULL, ville_site VARCHAR(255) NOT NULL, nom_projet VARCHAR(255) NOT NULL, annee_creation_projet VARCHAR(255) NOT NULL, id_projet VARCHAR(255) NOT NULL, departement_site VARCHAR(255) NOT NULL, folder_name VARCHAR(255) NOT NULL, old_folder_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE server (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, host VARCHAR(255) NOT NULL, port INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, id_crm VARCHAR(255) NOT NULL, intitule VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, old_intitule VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site_folder (site_id INT NOT NULL, folder_id INT NOT NULL, INDEX IDX_9C0CA792F6BD1646 (site_id), INDEX IDX_9C0CA792162CB942 (folder_id), PRIMARY KEY(site_id, folder_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE consultation ADD CONSTRAINT FK_964685A6C18272 FOREIGN KEY (projet_id) REFERENCES projet (id)');
        $this->addSql('ALTER TABLE folder ADD CONSTRAINT FK_ECA209CD1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id)');
        $this->addSql('ALTER TABLE site_folder ADD CONSTRAINT FK_9C0CA792F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_folder ADD CONSTRAINT FK_9C0CA792162CB942 FOREIGN KEY (folder_id) REFERENCES folder (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consultation DROP FOREIGN KEY FK_964685A6C18272');
        $this->addSql('ALTER TABLE folder DROP FOREIGN KEY FK_ECA209CD1844E6B7');
        $this->addSql('ALTER TABLE site_folder DROP FOREIGN KEY FK_9C0CA792F6BD1646');
        $this->addSql('ALTER TABLE site_folder DROP FOREIGN KEY FK_9C0CA792162CB942');
        $this->addSql('DROP TABLE consultation');
        $this->addSql('DROP TABLE folder');
        $this->addSql('DROP TABLE projet');
        $this->addSql('DROP TABLE server');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE site_folder');
    }
}

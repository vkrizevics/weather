<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250721194242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE station (
            id INT AUTO_INCREMENT NOT NULL, 
            _id INT NOT NULL, 
            station_id VARCHAR(16) NOT NULL, 
            name VARCHAR(64) NOT NULL, 
            wmo_id VARCHAR(10) DEFAULT NULL, 
            begin_date DATETIME DEFAULT NULL, 
            end_date DATETIME DEFAULT NULL, 
            latitude INT DEFAULT NULL, 
            longitude INT DEFAULT NULL, 
            gauss1 NUMERIC(8, 2) DEFAULT NULL, 
            gauss2 NUMERIC(8, 2) DEFAULT NULL, 
            geogr1 NUMERIC(8, 6) DEFAULT NULL, 
            geogr2 NUMERIC(8, 6) DEFAULT NULL, 
            elevation NUMERIC(5, 2) DEFAULT NULL, 
            elevation_pressure NUMERIC(5, 2) DEFAULT NULL, 
            UNIQUE INDEX UNIQ_9F39F8B16641C530 (_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE station');
    }
}
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251028112653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE temps_conseil (id INT AUTO_INCREMENT NOT NULL, conseil_id INT NOT NULL, mois INT NOT NULL, annee INT NOT NULL, INDEX IDX_4FBAD2AA668A3E03 (conseil_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE temps_conseil ADD CONSTRAINT FK_4FBAD2AA668A3E03 FOREIGN KEY (conseil_id) REFERENCES conseil (id)');
        $this->addSql('ALTER TABLE conseil DROP mois, DROP annee');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE temps_conseil DROP FOREIGN KEY FK_4FBAD2AA668A3E03');
        $this->addSql('DROP TABLE temps_conseil');
        $this->addSql('ALTER TABLE conseil ADD mois INT DEFAULT NULL, ADD annee INT DEFAULT NULL');
    }
}

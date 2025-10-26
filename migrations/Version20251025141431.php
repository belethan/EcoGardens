<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251025141431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX conseil_temps ON conseil');
        $this->addSql('DROP INDEX user_id_index ON user');
        $this->addSql('ALTER TABLE user ADD password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX user_email_uindex TO UNIQ_8D93D649E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX conseil_temps ON conseil (mois, annee)');
        $this->addSql('ALTER TABLE user DROP password');
        $this->addSql('CREATE INDEX user_id_index ON user (id)');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649e7927c74 TO user_email_uindex');
    }
}

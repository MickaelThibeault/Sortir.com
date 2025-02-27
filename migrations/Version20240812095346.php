<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240812095346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE groupe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE groupe_participant (groupe_id INT NOT NULL, participant_id INT NOT NULL, INDEX IDX_584218DF7A45358C (groupe_id), INDEX IDX_584218DF9D1C3019 (participant_id), PRIMARY KEY(groupe_id, participant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE groupe_participant ADD CONSTRAINT FK_584218DF7A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE groupe_participant ADD CONSTRAINT FK_584218DF9D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groupe_participant DROP FOREIGN KEY FK_584218DF7A45358C');
        $this->addSql('ALTER TABLE groupe_participant DROP FOREIGN KEY FK_584218DF9D1C3019');
        $this->addSql('DROP TABLE groupe');
        $this->addSql('DROP TABLE groupe_participant');
    }
}

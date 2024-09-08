<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240813095033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groupe ADD chef_de_groupe_id INT NOT NULL');
        $this->addSql('ALTER TABLE groupe ADD CONSTRAINT FK_4B98C21561AA3A6 FOREIGN KEY (chef_de_groupe_id) REFERENCES participant (id)');
        $this->addSql('CREATE INDEX IDX_4B98C21561AA3A6 ON groupe (chef_de_groupe_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groupe DROP FOREIGN KEY FK_4B98C21561AA3A6');
        $this->addSql('DROP INDEX IDX_4B98C21561AA3A6 ON groupe');
        $this->addSql('ALTER TABLE groupe DROP chef_de_groupe_id');
    }
}

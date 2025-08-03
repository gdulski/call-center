<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802142735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agent_availability (id INT AUTO_INCREMENT NOT NULL, agent_id INT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, INDEX IDX_49FB2D743414710B (agent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE agent_queue_type (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, queue_type_id INT NOT NULL, INDEX IDX_58C8CED0A76ED395 (user_id), INDEX IDX_58C8CED027CE17AD (queue_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE call_queue_volume_prediction (id INT AUTO_INCREMENT NOT NULL, queue_type_id INT NOT NULL, hour DATETIME NOT NULL, expected_calls INT NOT NULL, INDEX IDX_216E3F8D27CE17AD (queue_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE queue_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE schedule (id INT AUTO_INCREMENT NOT NULL, queue_type_id INT NOT NULL, status VARCHAR(255) NOT NULL, week_start_date DATE NOT NULL, INDEX IDX_5A3811FB27CE17AD (queue_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE schedule_shift_assignment (id INT AUTO_INCREMENT NOT NULL, schedule_id INT NOT NULL, user_id INT NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, INDEX IDX_7854B8FFA40BC2D5 (schedule_id), INDEX IDX_7854B8FFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agent_availability ADD CONSTRAINT FK_49FB2D743414710B FOREIGN KEY (agent_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE agent_queue_type ADD CONSTRAINT FK_58C8CED0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE agent_queue_type ADD CONSTRAINT FK_58C8CED027CE17AD FOREIGN KEY (queue_type_id) REFERENCES queue_type (id)');
        $this->addSql('ALTER TABLE call_queue_volume_prediction ADD CONSTRAINT FK_216E3F8D27CE17AD FOREIGN KEY (queue_type_id) REFERENCES queue_type (id)');
        $this->addSql('ALTER TABLE schedule ADD CONSTRAINT FK_5A3811FB27CE17AD FOREIGN KEY (queue_type_id) REFERENCES queue_type (id)');
        $this->addSql('ALTER TABLE schedule_shift_assignment ADD CONSTRAINT FK_7854B8FFA40BC2D5 FOREIGN KEY (schedule_id) REFERENCES schedule (id)');
        $this->addSql('ALTER TABLE schedule_shift_assignment ADD CONSTRAINT FK_7854B8FFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agent_availability DROP FOREIGN KEY FK_49FB2D743414710B');
        $this->addSql('ALTER TABLE agent_queue_type DROP FOREIGN KEY FK_58C8CED0A76ED395');
        $this->addSql('ALTER TABLE agent_queue_type DROP FOREIGN KEY FK_58C8CED027CE17AD');
        $this->addSql('ALTER TABLE call_queue_volume_prediction DROP FOREIGN KEY FK_216E3F8D27CE17AD');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FB27CE17AD');
        $this->addSql('ALTER TABLE schedule_shift_assignment DROP FOREIGN KEY FK_7854B8FFA40BC2D5');
        $this->addSql('ALTER TABLE schedule_shift_assignment DROP FOREIGN KEY FK_7854B8FFA76ED395');
        $this->addSql('DROP TABLE agent_availability');
        $this->addSql('DROP TABLE agent_queue_type');
        $this->addSql('DROP TABLE call_queue_volume_prediction');
        $this->addSql('DROP TABLE queue_type');
        $this->addSql('DROP TABLE schedule');
        $this->addSql('DROP TABLE schedule_shift_assignment');
        $this->addSql('DROP TABLE user');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250525134105 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign_metrics ADD cost_per_enrolled_student DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE campaign_metrics ALTER cost_per_application DROP NOT NULL');
        $this->addSql('ALTER TABLE campaign_metrics ALTER conversion_rate DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE campaign_metrics DROP cost_per_enrolled_student');
        $this->addSql('ALTER TABLE campaign_metrics ALTER cost_per_application SET NOT NULL');
        $this->addSql('ALTER TABLE campaign_metrics ALTER conversion_rate SET NOT NULL');
    }
}

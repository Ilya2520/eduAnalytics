<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250518174141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign_metrics ADD enrolled_students INT NOT NULL');
        $this->addSql('ALTER TABLE campaign_metrics ADD total_applications INT NOT NULL');
        $this->addSql('ALTER TABLE campaign_metrics ADD campaign_budget DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE campaign_metrics ADD advertising_costs DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE campaign_metrics ADD total_revenue DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE campaign_metrics ADD roi DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE campaign_metrics DROP enrolled_students');
        $this->addSql('ALTER TABLE campaign_metrics DROP total_applications');
        $this->addSql('ALTER TABLE campaign_metrics DROP campaign_budget');
        $this->addSql('ALTER TABLE campaign_metrics DROP advertising_costs');
        $this->addSql('ALTER TABLE campaign_metrics DROP total_revenue');
        $this->addSql('ALTER TABLE campaign_metrics DROP roi');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250412090722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE applicants_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE applications_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE campaign_metrics_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE marketing_campaigns_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE programs_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE reports_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE applicants (id INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, birth_date DATE DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, education VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7FAFCADBE7927C74 ON applicants (email)');
        $this->addSql('CREATE TABLE applications (id INT NOT NULL, applicant_id INT NOT NULL, program_id INT NOT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, documents JSON DEFAULT NULL, notes TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F7C966F097139001 ON applications (applicant_id)');
        $this->addSql('CREATE INDEX IDX_F7C966F03EB8070A ON applications (program_id)');
        $this->addSql('CREATE TABLE campaign_metrics (id INT NOT NULL, campaign_id INT NOT NULL, record_date DATE NOT NULL, impressions INT NOT NULL, clicks INT NOT NULL, applications_generated INT NOT NULL, cost_per_application DOUBLE PRECISION NOT NULL, conversion_rate DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8A3036CBF639F774 ON campaign_metrics (campaign_id)');
        $this->addSql('CREATE TABLE marketing_campaigns (id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, budget DOUBLE PRECISION NOT NULL, status VARCHAR(50) NOT NULL, channel VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE marketing_campaign_applicants (marketing_campaign_id INT NOT NULL, applicant_id INT NOT NULL, PRIMARY KEY(marketing_campaign_id, applicant_id))');
        $this->addSql('CREATE INDEX IDX_5599DA72893E6789 ON marketing_campaign_applicants (marketing_campaign_id)');
        $this->addSql('CREATE INDEX IDX_5599DA7297139001 ON marketing_campaign_applicants (applicant_id)');
        $this->addSql('CREATE TABLE programs (id INT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(50) NOT NULL, description TEXT DEFAULT NULL, degree VARCHAR(100) NOT NULL, duration INT NOT NULL, capacity INT NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE reports (id INT NOT NULL, requested_by_id INT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, parameters JSON NOT NULL, status VARCHAR(50) NOT NULL, file_path VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F11FA7454DA1E751 ON reports (requested_by_id)');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, department VARCHAR(50) NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C966F097139001 FOREIGN KEY (applicant_id) REFERENCES applicants (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C966F03EB8070A FOREIGN KEY (program_id) REFERENCES programs (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE campaign_metrics ADD CONSTRAINT FK_8A3036CBF639F774 FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE marketing_campaign_applicants ADD CONSTRAINT FK_5599DA72893E6789 FOREIGN KEY (marketing_campaign_id) REFERENCES marketing_campaigns (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE marketing_campaign_applicants ADD CONSTRAINT FK_5599DA7297139001 FOREIGN KEY (applicant_id) REFERENCES applicants (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reports ADD CONSTRAINT FK_F11FA7454DA1E751 FOREIGN KEY (requested_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE applicants_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE applications_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE campaign_metrics_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE marketing_campaigns_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE programs_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE reports_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE applications DROP CONSTRAINT FK_F7C966F097139001');
        $this->addSql('ALTER TABLE applications DROP CONSTRAINT FK_F7C966F03EB8070A');
        $this->addSql('ALTER TABLE campaign_metrics DROP CONSTRAINT FK_8A3036CBF639F774');
        $this->addSql('ALTER TABLE marketing_campaign_applicants DROP CONSTRAINT FK_5599DA72893E6789');
        $this->addSql('ALTER TABLE marketing_campaign_applicants DROP CONSTRAINT FK_5599DA7297139001');
        $this->addSql('ALTER TABLE reports DROP CONSTRAINT FK_F11FA7454DA1E751');
        $this->addSql('DROP TABLE applicants');
        $this->addSql('DROP TABLE applications');
        $this->addSql('DROP TABLE campaign_metrics');
        $this->addSql('DROP TABLE marketing_campaigns');
        $this->addSql('DROP TABLE marketing_campaign_applicants');
        $this->addSql('DROP TABLE programs');
        $this->addSql('DROP TABLE reports');
        $this->addSql('DROP TABLE users');
    }
}

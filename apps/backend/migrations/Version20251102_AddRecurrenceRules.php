<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251102_AddRecurrenceRules extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add recurrence_rules table for recurring tasks functionality';
    }

    public function up(Schema $schema): void
    {
        // Create recurrence_rules table
        $this->addSql('CREATE TABLE recurrence_rules (
            id SERIAL PRIMARY KEY,
            template_task_id INT NOT NULL,
            created_by_id INT NOT NULL,
            recurrence_type VARCHAR(20) NOT NULL,
            interval INT DEFAULT NULL,
            days_of_week JSON DEFAULT NULL,
            day_of_month INT DEFAULT NULL,
            month_of_year INT DEFAULT NULL,
            end_date DATE DEFAULT NULL,
            max_occurrences INT DEFAULT NULL,
            current_occurrences INT NOT NULL DEFAULT 0,
            next_occurrence_date TIMESTAMP WITHOUT TIME ZONE NOT NULL,
            time_of_day TIME WITHOUT TIME ZONE DEFAULT NULL,
            is_active BOOLEAN NOT NULL DEFAULT true,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT FK_recurrence_template_task FOREIGN KEY (template_task_id) REFERENCES "task" (id) ON DELETE CASCADE,
            CONSTRAINT FK_recurrence_created_by FOREIGN KEY (created_by_id) REFERENCES "users" (id)
        )');

        // Add indexes
        $this->addSql('CREATE INDEX IDX_recurrence_template_task ON recurrence_rules (template_task_id)');
        $this->addSql('CREATE INDEX IDX_recurrence_created_by ON recurrence_rules (created_by_id)');
        $this->addSql('CREATE INDEX IDX_recurrence_active_next ON recurrence_rules (is_active, next_occurrence_date)');

        // Add columns to task table
        $this->addSql('ALTER TABLE "task" ADD is_recurring_template BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE "task" ADD generated_from_rule_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "task" ADD CONSTRAINT FK_task_generated_from_rule FOREIGN KEY (generated_from_rule_id) REFERENCES recurrence_rules (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_task_generated_from_rule ON "task" (generated_from_rule_id)');
        $this->addSql('CREATE INDEX IDX_task_is_recurring_template ON "task" (is_recurring_template)');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraints first
        $this->addSql('ALTER TABLE "task" DROP CONSTRAINT IF EXISTS FK_task_generated_from_rule');

        // Drop indexes
        $this->addSql('DROP INDEX IF EXISTS IDX_task_generated_from_rule');
        $this->addSql('DROP INDEX IF EXISTS IDX_task_is_recurring_template');

        // Drop columns from task table
        $this->addSql('ALTER TABLE "task" DROP COLUMN IF EXISTS is_recurring_template');
        $this->addSql('ALTER TABLE "task" DROP COLUMN IF EXISTS generated_from_rule_id');
        
        // Drop recurrence_rules table
        $this->addSql('DROP TABLE IF EXISTS recurrence_rules');
    }
}

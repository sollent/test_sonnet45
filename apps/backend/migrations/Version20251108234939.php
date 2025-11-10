<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Performance Optimization: Add composite indexes for Task table
 *
 * Based on PERFORMANCE_OPTIMIZATION_PLAN.md - STAGE 4
 * This migration adds 15+ composite indexes to optimize queries for 2M+ tasks
 */
final class Version20251108234939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite and partial indexes for Task table to optimize performance';
    }

    public function up(Schema $schema): void
    {
        // ============================================
        // BASIC COMPOSITE INDEXES
        // ============================================

        // Most common query patterns: user + parent_task filtering
        $this->addSql('CREATE INDEX idx_task_user_parent ON "task" (user_id, parent_task_id)');

        // Filtering by status
        $this->addSql('CREATE INDEX idx_task_user_status ON "task" (user_id, status)');

        // Filtering by priority
        $this->addSql('CREATE INDEX idx_task_user_priority ON "task" (user_id, priority)');

        // Filtering archived tasks
        $this->addSql('CREATE INDEX idx_task_user_archived ON "task" (user_id, is_archived)');

        // Due date queries (calendar, overdue)
        $this->addSql('CREATE INDEX idx_task_user_due_date ON "task" (user_id, due_date)');

        // Analytics - completion tracking
        $this->addSql('CREATE INDEX idx_task_user_completed_at ON "task" (user_id, completed_at)');

        // Creation date queries (recent tasks)
        $this->addSql('CREATE INDEX idx_task_user_created_at ON "task" (user_id, created_at)');

        // ============================================
        // COMPOSITE INDEXES FOR COMPLEX FILTERING
        // ============================================

        // Subtasks filtering (not archived)
        $this->addSql('CREATE INDEX idx_task_user_parent_archived ON "task" (user_id, parent_task_id, is_archived)');

        // Subtasks with status filtering
        $this->addSql('CREATE INDEX idx_task_user_parent_status ON "task" (user_id, parent_task_id, status)');

        // Status + archived combination
        $this->addSql('CREATE INDEX idx_task_user_status_archived ON "task" (user_id, status, is_archived)');

        // Due date + status (overdue tasks)
        $this->addSql('CREATE INDEX idx_task_user_due_status ON "task" (user_id, due_date, status)');

        // ============================================
        // PARTIAL INDEXES FOR ANALYTICS
        // ============================================

        // Only completed tasks (for analytics)
        $this->addSql('CREATE INDEX idx_task_completed_date ON "task" (user_id, completed_at) WHERE completed_at IS NOT NULL');

        // Overdue tasks (not completed)
        $this->addSql('CREATE INDEX idx_task_overdue ON "task" (user_id, due_date, status) WHERE status != \'completed\'');

        // Active (non-archived) tasks with due dates
        $this->addSql('CREATE INDEX idx_task_active ON "task" (user_id, due_date) WHERE is_archived = false');

        // Root-level tasks (no parent)
        $this->addSql('CREATE INDEX idx_task_parent_null ON "task" (user_id, status) WHERE parent_task_id IS NULL');

        // Note: idx_task_recurring_template and idx_task_generated_from_rule are already created
        // in Version20251102_AddRecurrenceRules migration
    }

    public function down(Schema $schema): void
    {
        // Drop all indexes in reverse order (skipping those created in RecurrenceRules migration)
        $this->addSql('DROP INDEX IF EXISTS idx_task_parent_null');
        $this->addSql('DROP INDEX IF EXISTS idx_task_active');
        $this->addSql('DROP INDEX IF EXISTS idx_task_overdue');
        $this->addSql('DROP INDEX IF EXISTS idx_task_completed_date');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_due_status');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_status_archived');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_parent_status');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_parent_archived');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_created_at');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_completed_at');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_due_date');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_archived');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_priority');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_status');
        $this->addSql('DROP INDEX IF EXISTS idx_task_user_parent');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add partial index for onlyWithSubtasks filter optimization
 *
 * This index optimizes the subquery:
 * SELECT DISTINCT parent_task_id FROM task WHERE parent_task_id IS NOT NULL AND user_id = ?
 *
 * Used in TaskRepository::findActiveTasks() when onlyWithSubtasks=true
 */
final class Version20251109163500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add partial index on parent_task_id for tasks with parent (optimization for onlyWithSubtasks filter)';
    }

    public function up(Schema $schema): void
    {
        // Create partial index for parent_task_id IS NOT NULL
        // This optimizes the subquery that finds which tasks have subtasks
        $this->addSql('
            CREATE INDEX idx_task_has_parent
            ON "task" (user_id, parent_task_id)
            WHERE parent_task_id IS NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_task_has_parent');
    }
}

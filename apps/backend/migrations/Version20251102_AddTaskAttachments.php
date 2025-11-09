<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251102_AddTaskAttachments extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add task_attachments table for file uploads';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_attachments (
            id SERIAL PRIMARY KEY,
            task_id INT NOT NULL,
            uploaded_by_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            file_path VARCHAR(500) DEFAULT NULL,
            uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT FK_task_attachments_task FOREIGN KEY (task_id) REFERENCES "task" (id) ON DELETE CASCADE,
            CONSTRAINT FK_task_attachments_uploaded_by FOREIGN KEY (uploaded_by_id) REFERENCES "users" (id)
        )');
        
        $this->addSql('CREATE INDEX IDX_task_attachments_task ON task_attachments (task_id)');
        $this->addSql('CREATE INDEX IDX_task_attachments_uploaded_by ON task_attachments (uploaded_by_id)');
        $this->addSql('COMMENT ON COLUMN task_attachments.uploaded_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS task_attachments CASCADE');
    }
}


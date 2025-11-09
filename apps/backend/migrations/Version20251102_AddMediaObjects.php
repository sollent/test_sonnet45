<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251102_AddMediaObjects extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add media_objects table and task_media junction table';
    }

    public function up(Schema $schema): void
    {
        // Create media_objects table
        $this->addSql('CREATE TABLE media_objects (
            id SERIAL PRIMARY KEY,
            uploaded_by_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            thumbnail_path VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT FK_media_objects_uploaded_by FOREIGN KEY (uploaded_by_id) REFERENCES users (id)
        )');
        
        $this->addSql('CREATE INDEX IDX_media_objects_uploaded_by ON media_objects (uploaded_by_id)');
        $this->addSql('COMMENT ON COLUMN media_objects.created_at IS \'(DC2Type:datetime_immutable)\'');

        // Create task_media junction table
        $this->addSql('CREATE TABLE task_media (
            task_id INT NOT NULL,
            media_object_id INT NOT NULL,
            PRIMARY KEY(task_id, media_object_id),
            CONSTRAINT FK_task_media_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
            CONSTRAINT FK_task_media_media FOREIGN KEY (media_object_id) REFERENCES media_objects (id) ON DELETE CASCADE
        )');
        
        $this->addSql('CREATE INDEX IDX_task_media_task ON task_media (task_id)');
        $this->addSql('CREATE INDEX IDX_task_media_media ON task_media (media_object_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS task_media CASCADE');
        $this->addSql('DROP TABLE IF EXISTS media_objects CASCADE');
    }
}


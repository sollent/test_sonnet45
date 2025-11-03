<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251103000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add profile fields to users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD avatar VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD theme VARCHAR(20) NOT NULL DEFAULT \'light\'');
        $this->addSql('ALTER TABLE users ADD language VARCHAR(10) NOT NULL DEFAULT \'ru\'');
        $this->addSql('ALTER TABLE users ADD timezone VARCHAR(50) NOT NULL DEFAULT \'Europe/Moscow\'');
        $this->addSql('ALTER TABLE users ADD notification_settings JSON NOT NULL DEFAULT \'{"email":true,"push":true,"taskReminders":true,"taskAssignments":true,"taskCompletion":true,"weeklyDigest":false}\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP name');
        $this->addSql('ALTER TABLE users DROP avatar');
        $this->addSql('ALTER TABLE users DROP theme');
        $this->addSql('ALTER TABLE users DROP language');
        $this->addSql('ALTER TABLE users DROP timezone');
        $this->addSql('ALTER TABLE users DROP notification_settings');
    }
}

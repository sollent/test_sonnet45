<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix recurrence_rules sequence to prevent duplicate key violations
 */
final class Version20251106_FixRecurrenceRulesSequence extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix recurrence_rules_id_seq to sync with existing data';
    }

    public function up(Schema $schema): void
    {
        // Reset sequence to correct value based on max ID in table
        $this->addSql("SELECT setval('recurrence_rules_id_seq', (SELECT COALESCE(MAX(id), 0) + 1 FROM recurrence_rules), false)");
    }

    public function down(Schema $schema): void
    {
        // No need to revert - sequence fix is idempotent
    }
}

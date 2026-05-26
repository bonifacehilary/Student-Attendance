<?php
// src/Migrations/Version20260528000000.php
// Create notifications table

namespace Mpemba\Crud\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notifications table for student alerts and announcements';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('notifications');
        
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('student_id', 'integer', ['notnull' => true]);
        $table->addColumn('type', 'string', ['length' => 50, 'notnull' => true]); // attendance_alert, warning, announcement, confirmation
        $table->addColumn('title', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('message', 'text', ['notnull' => true]);
        $table->addColumn('icon_type', 'string', ['length' => 50, 'default' => 'info']); // warning, error, check_circle, info, priority_high
        $table->addColumn('color_type', 'string', ['length' => 20, 'default' => 'blue']); // red, orange, blue, green
        $table->addColumn('is_read', 'boolean', ['default' => false]);
        $table->addColumn('can_appeal', 'boolean', ['default' => false]); // For attendance alerts
        $table->addColumn('appeal_status', 'string', ['length' => 50, 'notnull' => false]); // pending, approved, rejected
        $table->addColumn('action_url', 'string', ['length' => 255, 'notnull' => false]); // Optional link
        $table->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'onUpdate' => true]);
        
        $table->setPrimaryKey(['id']);
        $table->addIndex(['student_id']);
        $table->addIndex(['student_id', 'created_at']);
        $table->addIndex(['is_read']);
        $table->addForeignKey(['student_id'], 'students', ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTableIfExists('notifications');
    }
}

<?php
// src/Migrations/Version20260528000000.php
// Create notifications table

namespace StudentAttendance\Migrations;

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

        $this->addSql("
        
            CREATE TABLE `notifications` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                icon_type VARCHAR(50) DEFAULT 'info',
                color_type VARCHAR(20) DEFAULT 'blue',
                is_read BOOLEAN DEFAULT FALSE,
                can_appeal BOOLEAN DEFAULT FALSE,
                appeal_status VARCHAR(50),
                action_url VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student_created (student_id, created_at),
                INDEX idx_is_read (is_read),
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(Schema $schema): void
    {
        $schema->dropTableIfExists('notifications');
    }
}

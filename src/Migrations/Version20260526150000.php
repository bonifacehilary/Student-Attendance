<?php

namespace Student_Attendance\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * QR Attendance Sessions Migration
 * Adds table for managing QR code attendance sessions
 */
final class Version20260526150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create attendance_qr_sessions table for QR code-based attendance marking';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('attendance_qr_sessions');
        
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('class_id', 'integer', ['notnull' => false]);
        $table->addColumn('session_name', 'string', ['length' => 255]);
        $table->addColumn('created_by', 'integer');
        $table->addColumn('created_date', 'datetime');
        $table->addColumn('expires_at', 'datetime');
        $table->addColumn('is_active', 'boolean', ['default' => true]);
        
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code']);
        $table->addIndex(['created_date']);
        $table->addIndex(['is_active']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('attendance_qr_sessions');
    }
}

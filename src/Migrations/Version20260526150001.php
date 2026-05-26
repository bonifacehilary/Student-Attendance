<?php

namespace Student_Attendance\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add QR Session Support to Attendance
 * Adds qr_session_id field to attendance table
 */
final class Version20260526150001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add qr_session_id field to attendance table for QR-based attendance tracking';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('attendance');
        
        if (!$table->hasColumn('qr_session_id')) {
            $table->addColumn('qr_session_id', 'integer', [
                'notnull' => false,
                'default' => null
            ]);
            
            // Add foreign key constraint
            $table->addForeignKeyConstraint(
                'attendance_qr_sessions',
                ['qr_session_id'],
                ['id'],
                ['onDelete' => 'SET NULL']
            );
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('attendance');
        
        if ($table->hasColumn('qr_session_id')) {
            $table->dropForeignKey('FK_ATTENDANCE_QR_SESSION');
            $table->dropColumn('qr_session_id');
        }
    }
}

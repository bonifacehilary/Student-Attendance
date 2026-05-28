<?php

declare(strict_types=1);

namespace StudentAttendance\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create students and attendance tables for EduAttend';
    }

    public function up(Schema $schema): void
    {
        // Students table
        $this->addSql('CREATE TABLE IF NOT EXISTS `students` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            admission_number VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )');

        // Attendance table
        $this->addSql('CREATE TABLE IF NOT EXISTS `attendance` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM("present", "absent", "late") DEFAULT "present",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_date (student_id, attendance_date)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS `attendance`');
        $this->addSql('DROP TABLE IF EXISTS `students`');
    }
}

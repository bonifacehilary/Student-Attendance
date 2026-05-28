<?php

declare(strict_types=1);

namespace StudentAttendance\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add student profile fields for class, phone, and profile photo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `students` 
            ADD COLUMN `student_class` VARCHAR(100) DEFAULT NULL, 
            ADD COLUMN `phone` VARCHAR(50) DEFAULT NULL,
            ADD COLUMN `profile_photo` VARCHAR(255) DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `students` 
            DROP COLUMN `profile_photo`, 
            DROP COLUMN `phone`, 
            DROP COLUMN `student_class`
        ');
    }
}

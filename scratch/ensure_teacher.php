<?php
/**
 * Fix teacher login: create table + reset demo account password.
 * Run: php scratch/ensure_teacher.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use StudentAttendance\Utils\TeacherAuth;

if (TeacherAuth::ensureDefaultTeacher()) {
    echo "OK — teacher@school.edu / teacher123 (class Grade 10A)\n";
    echo "Login: http://localhost:8000/pages/teacher/login.php\n";
    exit(0);
}

echo "FAILED — check database connection in .env\n";
exit(1);

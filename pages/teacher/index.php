<?php
require_once __DIR__ . '/../../config/bootstrap.php';

use StudentAttendance\Utils\TeacherAuth;

header('Location: ' . (TeacherAuth::check() ? '/pages/teacher/dashboard.php' : '/pages/teacher/login.php'));
exit;

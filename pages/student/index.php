<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (!empty($_SESSION['student_id'])) {
    header('Location: /pages/student/dashboard.php');
    exit;
}

header('Location: /pages/student/login.php');
exit;

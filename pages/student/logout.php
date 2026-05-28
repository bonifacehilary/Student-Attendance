<?php
// pages/student/logout.php
// Student logout handler

require_once __DIR__ . '/../../config/bootstrap.php';
session_destroy();

// Clear cookies if they were set
if (isset($_COOKIE['student_id'])) {
    setcookie('student_id', '', time() - 3600, '/');
}

header('Location: /pages/student/login.php');
exit;

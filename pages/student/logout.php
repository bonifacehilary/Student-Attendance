<?php
// pages/student/logout.php
// Student logout handler

session_start();
session_destroy();

// Clear cookies if they were set
if (isset($_COOKIE['student_id'])) {
    setcookie('student_id', '', time() - 3600, '/');
}

header('Location: /pages/student/login.php');
exit;

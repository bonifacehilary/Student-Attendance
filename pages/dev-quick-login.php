<?php
/**
 * Local dev only: skip login to preview student UI.
 */
require_once __DIR__ . '/../config/bootstrap.php';

$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host !== 'localhost:8000' && $host !== '127.0.0.1:8000') {
    http_response_code(403);
    exit('Dev quick-login is only available on localhost:8000');
}

$row = Utility::safeQuery(
    'SELECT id, name FROM students ORDER BY id ASC LIMIT 1',
    [],
    'SELECT',
    true
);

if (!$row) {
    header('Location: /pages/student/login.php');
    exit;
}

$_SESSION['student_id'] = (int) $row['id'];
$_SESSION['student_name'] = $row['name'];

header('Location: /pages/student/dashboard.php');
exit;

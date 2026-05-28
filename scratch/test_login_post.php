<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['identifier'] = 'alex@school.edu';
$_POST['password'] = 'password123';

require __DIR__ . '/../config/bootstrap.php';

$student = Utility::safeQuery(
    'SELECT id, email, admission_number, password_hash, name FROM students WHERE email = ? OR admission_number = ? LIMIT 1',
    ['alex@school.edu', 'alex@school.edu'],
    'SELECT',
    true
);

if ($student && password_verify('password123', $student['password_hash'])) {
    echo "LOGIN OK: {$student['name']} (id {$student['id']})\n";
} else {
    echo "LOGIN FAILED\n";
    var_export($student);
}

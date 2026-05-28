<?php
require __DIR__ . '/../config/bootstrap.php';

echo "=== Login diagnostics ===\n\n";

try {
    $GLOBALS['db']->executeQuery('SELECT 1');
    echo "Database: connected\n";
} catch (Throwable $e) {
    echo "Database: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

$params = $GLOBALS['db']->getParams();
echo "DB name: " . ($params['dbname'] ?? $params['path'] ?? 'unknown') . "\n\n";

try {
    $count = $GLOBALS['db']->fetchOne('SELECT COUNT(*) FROM students');
    echo "Students in table: $count\n\n";
} catch (Throwable $e) {
    echo "students table: " . $e->getMessage() . "\n";
    echo "Run: php bin/console migrations:migrate\n";
    exit(1);
}

$rows = $GLOBALS['db']->fetchAllAssociative(
    'SELECT id, email, admission_number, LEFT(password_hash, 20) AS hash_preview FROM students LIMIT 5'
);
foreach ($rows as $row) {
    echo "- {$row['email']} / {$row['admission_number']} (hash: {$row['hash_preview']}...)\n";
}

$testEmail = 'alex@school.edu';
$testPass = 'password123';
$row = $GLOBALS['db']->fetchAssociative(
    'SELECT id, password_hash FROM students WHERE email = ? LIMIT 1',
    [$testEmail]
);
if (!$row) {
    echo "\nTest user alex@school.edu: NOT FOUND - run php scratch/seed_students.php\n";
} else {
    $ok = password_verify($testPass, $row['password_hash']);
    echo "\nTest alex@school.edu + password123: " . ($ok ? 'OK' : 'PASSWORD MISMATCH') . "\n";
}

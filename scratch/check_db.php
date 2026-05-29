<?php
/**
 * Quick database check
 */
require __DIR__ . '/../config/bootstrap.php';

$db = $GLOBALS['db'];

// List all tables
echo "=== Tables ===\n";
$tables = $db->fetchAllAssociative("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
foreach ($tables as $t) {
    echo $t['name'] . "\n";
}

// Check students table schema
echo "\n=== Students Table Schema ===\n";
$schema = $db->fetchAllAssociative("PRAGMA table_info(students)");
foreach ($schema as $col) {
    echo $col['name'] . " (" . $col['type'] . ")\n";
}

// Count students
echo "\n=== Student Count ===\n";
$count = $db->fetchOne("SELECT COUNT(*) as cnt FROM students");
echo "Total: " . $count['cnt'] . "\n";

// List students
echo "\n=== Students ===\n";
$students = $db->fetchAllAssociative("SELECT id, name, email, admission_number FROM students");
foreach ($students as $s) {
    echo $s['id'] . " | " . $s['name'] . " | " . $s['email'] . " | " . $s['admission_number'] . "\n";
}

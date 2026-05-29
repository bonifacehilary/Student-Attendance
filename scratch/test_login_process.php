<?php
/**
 * Test the login process
 */
require __DIR__ . '/../config/bootstrap.php';

use StudentAttendance\Utils\Utility;

$testEmail = 'alex@school.edu';
$testPassword = 'password123';

echo "=== Testing Login Process ===\n\n";

// Step 1: Query the student
echo "Step 1: Querying student by email...\n";
try {
    $student = Utility::safeQuery(
        'SELECT id, email, admission_number, password_hash, name FROM students WHERE email = ? OR admission_number = ? LIMIT 1',
        [$testEmail, 'STU2024001'],
        'SELECT',
        true
    );
    
    if ($student) {
        echo "✓ Student found:\n";
        echo "  ID: " . $student['id'] . "\n";
        echo "  Name: " . $student['name'] . "\n";
        echo "  Email: " . $student['email'] . "\n";
    } else {
        echo "✗ Student not found!\n";
        exit(1);
    }
} catch (\Throwable $e) {
    echo "✗ Query error: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Verify password
echo "\nStep 2: Verifying password...\n";
$passwordMatch = password_verify($testPassword, $student['password_hash']);
echo "Password '" . $testPassword . "' matches: " . ($passwordMatch ? "YES ✓" : "NO ✗") . "\n";

if (!$passwordMatch) {
    echo "✗ Password verification failed!\n";
    exit(1);
}

// Step 3: Set session
echo "\nStep 3: Setting session values...\n";
$_SESSION['student_id'] = (int) $student['id'];
$_SESSION['student_name'] = (string) $student['name'];
echo "Session student_id: " . $_SESSION['student_id'] . " (type: " . gettype($_SESSION['student_id']) . ")\n";
echo "Session student_name: " . $_SESSION['student_name'] . " (type: " . gettype($_SESSION['student_name']) . ")\n";

// Step 4: Verify session
echo "\nStep 4: Verifying session...\n";
echo "Session student_id isset: " . (isset($_SESSION['student_id']) ? "YES ✓" : "NO ✗") . "\n";
echo "Session student_id empty: " . (empty($_SESSION['student_id']) ? "YES ✗" : "NO ✓") . "\n";
echo "Session student_id value: " . $_SESSION['student_id'] . "\n";

// Step 5: Test StudentAuth
echo "\nStep 5: Testing StudentAuth class...\n";
require __DIR__ . '/../utils/StudentAuth.php';

use StudentAttendance\Utils\StudentAuth;

echo "StudentAuth::check(): " . (StudentAuth::check() ? "TRUE ✓" : "FALSE ✗") . "\n";
echo "StudentAuth::studentId(): " . StudentAuth::studentId() . "\n";

// Step 6: Test redirect headers
echo "\nStep 6: Testing redirect...\n";
echo "Would redirect to: /pages/student/dashboard.php\n";
echo "Headers to send:\n";
echo "  Location: /pages/student/dashboard.php\n";

echo "\n=== Login Test Complete ===\n";
echo "Login process works! ✓\n";

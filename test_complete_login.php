<?php
/**
 * Comprehensive Student Login Test
 * 
 * This file simulates a complete student login workflow
 * Run: php test_complete_login.php
 */

echo "\n===========================================\n";
echo "   COMPLETE STUDENT LOGIN TEST\n";
echo "===========================================\n\n";

require __DIR__ . '/config/bootstrap.php';

use StudentAttendance\Utils\Utility;
use StudentAttendance\Utils\StudentAuth;

$tests = [];
$testsPassed = 0;
$testsFailed = 0;

// Test 1: Database Connection
echo "TEST 1: Database Connection\n";
try {
    $db = $GLOBALS['db'];
    $result = $db->fetchOne('SELECT 1');
    echo "  ✓ Database connected\n";
    $tests['db_connection'] = 'PASS';
    $testsPassed++;
} catch (\Throwable $e) {
    echo "  ✗ Database connection failed: " . $e->getMessage() . "\n";
    $tests['db_connection'] = 'FAIL';
    $testsFailed++;
    exit(1);
}

// Test 2: Students Table Exists
echo "\nTEST 2: Students Table Exists\n";
try {
    $count = $db->fetchOne('SELECT COUNT(*) as cnt FROM students');
    echo "  ✓ Students table exists with " . $count['cnt'] . " records\n";
    $tests['students_table'] = 'PASS';
    $testsPassed++;
} catch (\Throwable $e) {
    echo "  ✗ Students table check failed: " . $e->getMessage() . "\n";
    $tests['students_table'] = 'FAIL';
    $testsFailed++;
    exit(1);
}

// Test 3: Query Test Student
echo "\nTEST 3: Query Test Student\n";
try {
    $student = $db->fetchAssociative(
        'SELECT id, name, email, admission_number, password_hash FROM students WHERE email = ? LIMIT 1',
        ['alex@school.edu']
    );
    if ($student) {
        echo "  ✓ Found test student: " . $student['name'] . " (" . $student['email'] . ")\n";
        $tests['query_student'] = 'PASS';
        $testsPassed++;
    } else {
        echo "  ✗ Test student not found. Run: php scratch/setup_local.php\n";
        $tests['query_student'] = 'FAIL';
        $testsFailed++;
        exit(1);
    }
} catch (\Throwable $e) {
    echo "  ✗ Query failed: " . $e->getMessage() . "\n";
    $tests['query_student'] = 'FAIL';
    $testsFailed++;
    exit(1);
}

// Test 4: Password Verification
echo "\nTEST 4: Password Verification\n";
try {
    $testPassword = 'password123';
    $verified = password_verify($testPassword, $student['password_hash']);
    if ($verified) {
        echo "  ✓ Password verification successful\n";
        $tests['password_verify'] = 'PASS';
        $testsPassed++;
    } else {
        echo "  ✗ Password verification failed. Hash might be corrupted.\n";
        $tests['password_verify'] = 'FAIL';
        $testsFailed++;
    }
} catch (\Throwable $e) {
    echo "  ✗ Password check failed: " . $e->getMessage() . "\n";
    $tests['password_verify'] = 'FAIL';
    $testsFailed++;
}

// Test 5: Session Setting
echo "\nTEST 5: Session Setting\n";
try {
    $_SESSION['test_student_id'] = (int) $student['id'];
    $_SESSION['test_student_name'] = (string) $student['name'];
    
    if ($_SESSION['test_student_id'] == $student['id']) {
        echo "  ✓ Session values set correctly\n";
        echo "    - student_id: " . $_SESSION['test_student_id'] . " (type: " . gettype($_SESSION['test_student_id']) . ")\n";
        echo "    - student_name: " . $_SESSION['test_student_name'] . "\n";
        $tests['session_setting'] = 'PASS';
        $testsPassed++;
    } else {
        echo "  ✗ Session setting failed\n";
        $tests['session_setting'] = 'FAIL';
        $testsFailed++;
    }
} catch (\Throwable $e) {
    echo "  ✗ Session error: " . $e->getMessage() . "\n";
    $tests['session_setting'] = 'FAIL';
    $testsFailed++;
}

// Test 6: StudentAuth Check
echo "\nTEST 6: StudentAuth Class\n";
try {
    // Test check() method
    $_SESSION['student_id'] = (int) $student['id'];
    if (StudentAuth::check()) {
        echo "  ✓ StudentAuth::check() works\n";
    } else {
        echo "  ✗ StudentAuth::check() failed\n";
        $testsFailed++;
    }
    
    // Test studentId() method
    $id = StudentAuth::studentId();
    if ($id == $student['id']) {
        echo "  ✓ StudentAuth::studentId() returns: " . $id . "\n";
        $tests['studentauth_methods'] = 'PASS';
        $testsPassed++;
    } else {
        echo "  ✗ StudentAuth::studentId() failed\n";
        $tests['studentauth_methods'] = 'FAIL';
        $testsFailed++;
    }
} catch (\Throwable $e) {
    echo "  ✗ StudentAuth error: " . $e->getMessage() . "\n";
    $tests['studentauth_methods'] = 'FAIL';
    $testsFailed++;
}

// Test 7: Activity Logging
echo "\nTEST 7: Activity Logging (Optional)\n";
try {
    Utility::insert('activity_logs', [
        'user_id' => (int) $student['id'],
        'action' => 'test_login',
        'details' => 'Test login at ' . date('Y-m-d H:i:s'),
        'ip_address' => '127.0.0.1',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    echo "  ✓ Activity logged successfully\n";
    $tests['activity_logging'] = 'PASS';
    $testsPassed++;
} catch (\Throwable $e) {
    if (str_contains($e->getMessage(), 'no such table')) {
        echo "  ⓘ Activity logs table doesn't exist (optional)\n";
        $tests['activity_logging'] = 'SKIP';
    } else {
        echo "  ✗ Activity logging failed: " . $e->getMessage() . "\n";
        $tests['activity_logging'] = 'FAIL';
        $testsFailed++;
    }
}

// Final Summary
echo "\n===========================================\n";
echo "TEST SUMMARY\n";
echo "===========================================\n";
echo "Passed: $testsPassed\n";
echo "Failed: $testsFailed\n";

if ($testsFailed === 0) {
    echo "\n✓ All critical tests passed!\n";
    echo "\nYou can now:\n";
    echo "  1. Visit http://localhost:8000/pages/student/login.php\n";
    echo "  2. Login with: alex@school.edu / password123\n";
    echo "  3. Should redirect to dashboard\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Check errors above.\n";
    exit(1);
}

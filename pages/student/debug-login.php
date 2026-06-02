<?php
/**
 * Debug page for testing student login
 * Access: http://localhost:8000/pages/student/debug-login.php
 */

require_once __DIR__ . '/../../config/bootstrap.php';

$db = $GLOBALS['db'] ?? null;
$errors = [];
$success = [];

// Test 1: Database connection
try {
    if (!$db) {
        $errors[] = 'Database not initialized in bootstrap.php';
    } else {
        $success[] = 'Database connection: OK';
    }
} catch (\Throwable $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
}

// Test 2: Students table exists
try {
    $tables = $db->fetchAllAssociative("SELECT name FROM sqlite_master WHERE type='table' AND name='students'");
    if (empty($tables)) {
        $errors[] = 'Students table not found. Run: php scratch/setup_local.php';
    } else {
        $success[] = 'Students table: EXISTS';
    }
} catch (\Throwable $e) {
    $errors[] = 'Table check error: ' . $e->getMessage();
}

// Test 3: Count students
try {
    $count = $db->fetchOne('SELECT COUNT(*) as cnt FROM students');
    $studentCount = $count['cnt'] ?? 0;
    if ($studentCount === 0) {
        $errors[] = 'No students found in database. Run: php scratch/setup_local.php';
    } else {
        $success[] = "Students in database: $studentCount";
    }
} catch (\Throwable $e) {
    $errors[] = 'Count error: ' . $e->getMessage();
}

// Test 4: List all students
try {
    $students = $db->fetchAllAssociative('SELECT id, name, email, admission_number FROM students');
    if (!empty($students)) {
        $success[] = 'Students found:';
        foreach ($students as $s) {
            $success[] = '  - ' . $s['name'] . ' (' . $s['email'] . ', ' . $s['admission_number'] . ')';
        }
    }
} catch (\Throwable $e) {
    $errors[] = 'List students error: ' . $e->getMessage();
}

$firstStudent = null;

// Test 5: Check password hashes exist without exposing any sample credential
try {
    $firstStudent = $db->fetchAssociative(
        'SELECT id, email, admission_number, password_hash FROM students LIMIT 1'
    );
    if ($firstStudent) {
        if (!empty($firstStudent['password_hash'])) {
            $success[] = 'Password hash present for first student: OK';
        } else {
            $errors[] = 'First student is missing a password hash. Re-run: php scratch/setup_local.php';
        }
    }
} catch (\Throwable $e) {
    $errors[] = 'Password test error: ' . $e->getMessage();
}

// Test 6: Try the actual login lookup shape with the first available student
try {
    $firstIdentifier = $firstStudent['email'] ?? $firstStudent['admission_number'] ?? '';
    $student = $db->fetchAssociative(
        'SELECT id, email, admission_number, password_hash, name FROM students WHERE email = ? OR admission_number = ? LIMIT 1',
        [$firstIdentifier, $firstIdentifier]
    );
    if ($student) {
        $success[] = 'Login query for first student identifier: FOUND';
    } else {
        $errors[] = 'Login query returned no results for first student identifier';
    }
} catch (\Throwable $e) {
    $errors[] = 'Login query error: ' . $e->getMessage();
}

// Test 7: Session test
try {
    $_SESSION['test'] = 'session_works';
    $success[] = 'Session: Can write to $_SESSION';
    unset($_SESSION['test']);
} catch (\Throwable $e) {
    $errors[] = 'Session error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .info { background: #e7f3ff; color: #004085; border: 1px solid #b3d9ff; padding: 10px; margin: 10px 0; border-radius: 4px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Login Debugging</h1>
        
        <h2>Test Results:</h2>
        
        <?php if (!empty($errors)): ?>
            <h3>❌ Errors:</h3>
            <?php foreach ($errors as $error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <h3>✅ Success:</h3>
            <?php foreach ($success as $msg): ?>
                <div class="success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2>Actions:</h2>
        <ul>
            <li><a href="/pages/student/login.php">Go to Login Page</a></li>
            <li><a href="/pages/student/splash.php">Go to Splash Page</a></li>
        </ul>
    </div>
</body>
</html>

<?php
/**
 * Local setup without MySQL: SQLite + test students.
 * Run: php scratch/setup_local.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);

$envPath = $root . '/.env';
file_put_contents(
    $envPath,
    "DB_DRIVER=pdo_sqlite\nDB_PATH=config/db.sqlite\nDB_NAME=student_attendance\n",
    LOCK_EX
);
echo "Using SQLite: config/db.sqlite\n\n";

if (!extension_loaded('pdo_sqlite')) {
    echo "ERROR: PHP pdo_sqlite extension is disabled.\n";
    echo "Enable extension=pdo_sqlite in php.ini, then run this script again.\n";
    exit(1);
}

require $root . '/config/bootstrap.php';

$db = $GLOBALS['db'];

$schema = [
    'CREATE TABLE IF NOT EXISTS students (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        admission_number VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        student_class VARCHAR(100) DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        profile_photo VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )',
    'CREATE TABLE IF NOT EXISTS attendance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        attendance_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT "present",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(student_id, attendance_date),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )',
    'CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT "info",
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )',
    'CREATE TABLE IF NOT EXISTS password_resets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_id INTEGER NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )',
    'CREATE TABLE IF NOT EXISTS attendance_qr_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code VARCHAR(100) NOT NULL UNIQUE,
        class_id INTEGER DEFAULT NULL,
        session_name VARCHAR(255) DEFAULT NULL,
        created_by INTEGER DEFAULT NULL,
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME DEFAULT NULL,
        is_active INTEGER DEFAULT 1
    )',
    'CREATE TABLE IF NOT EXISTS teachers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        assigned_class VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )',
];

echo "Creating tables...\n";
foreach ($schema as $sql) {
    $db->executeStatement($sql);
}

$testStudents = [
    ['Alex Rivers', 'alex@school.edu', 'STU2024001', 'password123', 'Grade 10A'],
    ['Jordan Smith', 'jordan@school.edu', 'STU2024002', 'password123', 'Grade 10A'],
    ['Sam Johnson', 'sam@school.edu', 'STU2024003', 'password123', 'Grade 10B'],
];

echo "Seeding students...\n";
foreach ($testStudents as [$name, $email, $adm, $pass, $class]) {
    $exists = $db->fetchOne('SELECT id FROM students WHERE email = ?', [$email]);
    if ($exists) {
        $db->executeStatement(
            'UPDATE students SET student_class = ? WHERE email = ?',
            [$class, $email]
        );
        echo "  updated class: $email → $class\n";
        continue;
    }
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db->executeStatement(
        'INSERT INTO students (name, email, admission_number, password_hash, student_class) VALUES (?, ?, ?, ?, ?)',
        [$name, $email, $adm, $hash, $class]
    );
    echo "  created: $email / $adm ($class)\n";
}

echo "Upgrading schema...\n";
try {
    $db->executeStatement('ALTER TABLE attendance ADD COLUMN qr_session_id INTEGER DEFAULT NULL');
    echo "  added attendance.qr_session_id\n";
} catch (\Throwable $e) {
    // column exists
}
foreach (
    [
        'ALTER TABLE notifications ADD COLUMN can_appeal INTEGER DEFAULT 0',
        'ALTER TABLE notifications ADD COLUMN appeal_status VARCHAR(50) DEFAULT NULL',
    ] as $alterSql
) {
    try {
        $db->executeStatement($alterSql);
        echo "  applied: $alterSql\n";
    } catch (\Throwable $e) {
        // exists
    }
}
try {
    $db->executeStatement(
        'ALTER TABLE password_resets ADD COLUMN token_hash VARCHAR(255) DEFAULT NULL'
    );
    echo "  added password_resets.token_hash\n";
} catch (\Throwable $e) {
    // exists
}

echo "Seeding sample notifications...\n";
foreach ($db->fetchAllAssociative('SELECT id FROM students') as $stu) {
    $sid = (int) $stu['id'];
    $exists = $db->fetchOne('SELECT id FROM notifications WHERE student_id = ? LIMIT 1', [$sid]);
    if ($exists) {
        continue;
    }
    $db->executeStatement(
        'INSERT INTO notifications (student_id, title, message, type, is_read) VALUES (?, ?, ?, ?, 0)',
        [
            $sid,
            'Welcome to EduAttend',
            'Your student portal is ready. Mark attendance via QR when your teacher shares a code.',
            'announcement',
        ]
    );
    echo "  notification for student #$sid\n";
}

echo "Seeding teacher...\n";
$teacherEmail = 'teacher@school.edu';
$teacherHash = password_hash('teacher123', PASSWORD_BCRYPT);
$teacherExists = $db->fetchOne('SELECT id FROM teachers WHERE email = ?', [$teacherEmail]);
if (!$teacherExists) {
    $db->executeStatement(
        'INSERT INTO teachers (name, email, password_hash, assigned_class) VALUES (?, ?, ?, ?)',
        ['Ms. Parker', $teacherEmail, $teacherHash, 'Grade 10A']
    );
    echo "  created: $teacherEmail / teacher123 (Grade 10A)\n";
} else {
    $db->executeStatement(
        'UPDATE teachers SET password_hash = ?, assigned_class = ?, name = ? WHERE email = ?',
        [$teacherHash, 'Grade 10A', 'Ms. Parker', $teacherEmail]
    );
    echo "  reset password: $teacherEmail / teacher123 (Grade 10A)\n";
}

echo "\n=== Ready ===\n";
echo "Student: http://localhost:8000/pages/student/login.php\n";
echo "  alex@school.edu / STU2024001 — password123\n";
echo "Teacher: http://localhost:8000/pages/teacher/login.php\n";
echo "  teacher@school.edu — teacher123 (class Grade 10A)\n";
echo "Admin:   http://localhost:8000/pages/admin/login.php\n";
echo "  admin@school.edu — admin123\n";

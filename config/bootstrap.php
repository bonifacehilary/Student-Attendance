<?php

use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env file
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

$connectionParams = [
    'driver'   => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
    'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port'     => $_ENV['DB_PORT'] ?? '3306',
    'dbname'   => $_ENV['DB_NAME'] ?? 'field_teaching',
    'user'     => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
];

if (($connectionParams['driver'] ?? '') === 'pdo_sqlite') {
    $dbPath = $_ENV['DB_PATH'] ?? 'config/db.sqlite';
    // If path starts with config/, it's relative to root
    if (strpos($dbPath, 'config/') === 0) {
        $connectionParams['path'] = __DIR__ . '/../' . $dbPath;
    } else {
        $connectionParams['path'] = __DIR__ . '/' . $dbPath;
    }
}

$db = DriverManager::getConnection($connectionParams);
$GLOBALS['db'] = $db;

// Load shared utils (Composer PSR-4: StudentAttendance\Utils\ -> utils/)
if (!class_exists('Utility', false)) {
    class_alias(\StudentAttendance\Utils\Utility::class, 'Utility');
}

// Start session with a shared cookie path so auth works across pages and API endpoints.
if (PHP_SAPI !== 'cli') {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => '/',
        'domain' => $cookieParams['domain'],
        'secure' => $cookieParams['secure'],
        'httponly' => $cookieParams['httponly'],
        'samesite' => $cookieParams['samesite'] ?? 'Lax',
    ]);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Restore remembered student login if session is missing.
    if (empty($_SESSION['student_id']) && !empty($_COOKIE['student_id'])) {
        $rememberedStudentId = (int) $_COOKIE['student_id'];
        if ($rememberedStudentId > 0) {
            try {
                \StudentAttendance\Utils\UserManagement::ensureSchema();
                $student = Utility::safeQuery(
                    'SELECT id, name, COALESCE(is_active, 1) AS is_active FROM students WHERE id = ? LIMIT 1',
                    [$rememberedStudentId],
                    'SELECT',
                    true
                );
                if ($student && !empty($student['is_active'])) {
                    $_SESSION['student_id'] = (int) $student['id'];
                    $_SESSION['student_name'] = $student['name'] ?? '';
                } else {
                    setcookie('student_id', '', time() - 3600, '/');
                }
            } catch (\Throwable $e) {
                error_log('Bootstrap student auto-login error: ' . $e->getMessage());
            }
        }
    }
}

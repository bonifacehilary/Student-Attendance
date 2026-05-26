<?php

use Doctrine\DBAL\DriverManager;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env file
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

// Simple autoloader for Mpemba namespace
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $class = str_replace('Mpemba\\', '', $class);
    $class = str_replace('\\', '/', $class);

    $file = __DIR__ . '/../' . $class . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

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

// Start session with a shared cookie path so auth sessions work across the storefront and API endpoints.
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
}
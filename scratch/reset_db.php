<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbName = 'ecommerce';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("DROP DATABASE IF EXISTS `$dbName`;");
    $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "Database `$dbName` reset successfully.\n";
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}

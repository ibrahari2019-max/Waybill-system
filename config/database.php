<?php
/**
 * Goods Tracking and Transportation Management System
 * Case Study of Funtua Garage, Samaru, Zaria
 * Database Configuration & Connection
 */

// Error reporting for development (clean in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database Credentials
$db_host = 'localhost';
$db_name = 'goods_tracking_system';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

// Establish PDO Connection
try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // If database doesn't exist, try creating it and importing
    try {
        $pdo_init = new PDO("mysql:host={$db_host};charset={$db_charset}", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Import sql file if exists
        $sql_file = __DIR__ . '/../database/goods_tracking_system.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            $pdo->exec($sql);
        }
    } catch (PDOException $ex) {
        die("Database connection failed. Please ensure MySQL is running in XAMPP. Error: " . htmlspecialchars($ex->getMessage()));
    }
}

// Compute dynamic Base URL
if (!defined('BASE_URL')) {
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs'));
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $subDir = trim(str_replace($docRoot, '', $projectRoot), '/');
    $baseUrl = '/' . ($subDir ? $subDir . '/' : '');
    define('BASE_URL', $baseUrl);
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Funtua Garage Goods Tracking System');
    define('APP_SUBTITLE', 'Samaru, Zaria Goods Transportation Management');
}

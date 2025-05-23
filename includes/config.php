<?php
// Database configuration
$host = '192.168.0.9';
$dbname = 'library_db';
$username = 'root';
$password = '';

// Timezone setting
date_default_timezone_set('Asia/Jakarta');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Application settings
define('SITE_NAME', 'Library Management System');
define('ADMIN_EMAIL', 'admin@library.com');
define('ITEMS_PER_PAGE', 10);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('DEFAULT_BORROW_DAYS', 14);

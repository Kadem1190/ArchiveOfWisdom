<?php
// Prevent any output before JSON
ob_start();

// Disable error display
ini_set('display_errors', 0);
error_reporting(0);

// Set JSON content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = 'localhost';
$dbname = 'db_perpustakaan';
$username = 'root';
$password = '';

// Timezone setting
date_default_timezone_set('Asia/Jakarta');

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Clear any output buffer
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Function to safely output JSON and exit
function outputJSON($data) {
    // Clear any output buffer
    ob_end_clean();
    echo json_encode($data);
    exit;
}
?>
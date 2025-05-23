<?php
// Start output buffering
ob_start();

// Set error handling to catch issues
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method is allowed',
        'method' => $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

// Database connection
$host = 'localhost';
$db = 'db_perpustakaan';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get request data
$input = file_get_contents('php://input');
if (empty($input)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'No input data received'
    ]);
    exit;
}

try {
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Failed to parse input: ' . $e->getMessage()]);
    exit;
}

if (!isset($data['anggota']) || !isset($data['username']) || !isset($data['password'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$anggota = $data['anggota'];
$username = $data['username'];
$password = $data['password']; // No hashing

// Validate anggota data
if (empty($anggota['name']) || empty($anggota['nim']) || empty($anggota['class']) || 
    empty($anggota['date_of_birth']) || empty($anggota['gender'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Missing required anggota data']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Check if NIM already exists
    $stmt = $conn->prepare("SELECT anggota_id FROM anggota WHERE nim = ?");
    $stmt->execute([$anggota['nim']]);
    
    if ($stmt->rowCount() > 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'NIM already registered']);
        exit;
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Username already taken']);
        exit;
    }
    
    // Insert anggota data
    $stmt = $conn->prepare("INSERT INTO anggota (name, nim, class, address, place_of_birth, date_of_birth, gender) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $anggota['name'],
        $anggota['nim'],
        $anggota['class'],
        $anggota['address'] ?? null,
        $anggota['place_of_birth'] ?? null,
        $anggota['date_of_birth'],
        $anggota['gender']
    ]);
    
    $anggotaId = $conn->lastInsertId();
    
    // Insert user data with plain password
    $stmt = $conn->prepare("INSERT INTO users (anggota_id, username, password, full_name, role, status, registration_status) 
                           VALUES (?, ?, ?, ?, 'user', 'inactive', 'pending')");
    
    $stmt->execute([$anggotaId, $username, $password, $anggota['name']]);
    
    $conn->commit();
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful. Please wait for admin approval.',
        'anggota_id' => $anggotaId
    ]);
    
} catch(PDOException $e) {
    $conn->rollBack();
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
?>

<?php
// Start output buffering
ob_start();

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

if (!isset($data['username']) || !isset($data['password'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

$username = $data['username'];
$password = $data['password'];

try {
    // Get user data
    $stmt = $conn->prepare("SELECT u.*, a.* FROM users u 
                           JOIN anggota a ON u.anggota_id = a.anggota_id 
                           WHERE u.username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() == 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Direct password comparison (no hashing)
    if ($password !== $user['password']) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }
    
    // Check if user is banned
    if ($user['status'] == 'banned') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Your account has been banned. Please contact the administrator.']);
        exit;
    }
    
    // Check if user is active and approved
    if ($user['status'] != 'active' || $user['registration_status'] != 'approved') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Your account is not active or pending approval.']);
        exit;
    }
    
    // Generate a simple token
    $token = bin2hex(random_bytes(32));
    
    // Prepare user data for response
    $userData = [
        'user_id' => $user['user_id'],
        'anggota_id' => $user['anggota_id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'role' => $user['role'],
        'status' => $user['status'],
        'registration_status' => $user['registration_status'],
        'created_at' => $user['created_at']
    ];
    
    // Prepare anggota data for response
    $anggotaData = [
        'anggota_id' => $user['anggota_id'],
        'name' => $user['name'],
        'nim' => $user['nim'],
        'class' => $user['class'],
        'address' => $user['address'],
        'place_of_birth' => $user['place_of_birth'],
        'date_of_birth' => $user['date_of_birth'],
        'gender' => $user['gender']
    ];
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $userData,
        'anggota' => $anggotaData
    ]);
    
} catch(PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
}
?>

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if (!isset($_GET['anggota_id'])) {
    echo json_encode(['success' => false, 'message' => 'Anggota ID is required']);
    exit;
}

$anggotaId = $_GET['anggota_id'];

try {
    $stmt = $pdo->prepare("SELECT status, registration_status FROM users WHERE anggota_id = ?");
    $stmt->execute([$anggotaId]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'status' => $user['status'],
        'registration_status' => $user['registration_status']
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to check status: ' . $e->getMessage()]);
}
?>
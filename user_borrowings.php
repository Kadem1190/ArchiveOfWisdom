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
    $stmt = $pdo->prepare("SELECT b.*, bk.title as book_title, bk.author as book_author 
                           FROM borrowings b
                           JOIN books bk ON b.book_id = bk.book_id
                           WHERE b.anggota_id = ?
                           ORDER BY b.borrow_date DESC");
    
    $stmt->execute([$anggotaId]);
    $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for overdue books
    $today = date('Y-m-d');
    foreach ($borrowings as &$borrowing) {
        if ($borrowing['status'] == 'borrowed' && $borrowing['due_date'] < $today) {
            $updateStmt = $pdo->prepare("UPDATE borrowings SET status = 'overdue' WHERE borrowing_id = ?");
            $updateStmt->execute([$borrowing['borrowing_id']]);
            $borrowing['status'] = 'overdue';
        }
    }
    
    echo json_encode($borrowings);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch borrowings: ' . $e->getMessage()]);
}
?>
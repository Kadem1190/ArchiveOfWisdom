<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$host = 'localhost';
$db = 'db_perpustakaan';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Clean output buffer and send error response
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get anggota_id from request
if (!isset($_GET['anggota_id'])) {
    // Clean output buffer and send error response
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Anggota ID is required']);
    exit;
}

$anggotaId = $_GET['anggota_id'];

try {
    // Get user borrowings with book details
    $stmt = $conn->prepare("SELECT b.*, bk.title as book_title, bk.author as book_author 
                           FROM borrowings b
                           JOIN books bk ON b.book_id = bk.book_id
                           WHERE b.anggota_id = :anggota_id
                           ORDER BY b.borrow_date DESC");
    
    $stmt->bindParam(':anggota_id', $anggotaId);
    $stmt->execute();
    
    $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for overdue books and update status
    $today = date('Y-m-d');
    foreach ($borrowings as &$borrowing) {
        // Ensure numeric fields are properly typed
        $borrowing['borrowing_id'] = (int)$borrowing['borrowing_id'];
        $borrowing['anggota_id'] = (int)$borrowing['anggota_id'];
        $borrowing['book_id'] = (int)$borrowing['book_id'];
        
        if ($borrowing['status'] == 'borrowed' && $borrowing['due_date'] < $today) {
            // Update status in database
            $updateStmt = $conn->prepare("UPDATE borrowings SET status = 'overdue' WHERE borrowing_id = :borrowing_id");
            $updateStmt->bindParam(':borrowing_id', $borrowing['borrowing_id']);
            $updateStmt->execute();
            
            // Update status in response
            $borrowing['status'] = 'overdue';
        }
    }
    
    // Clean output buffer and send JSON response
    ob_end_clean();
    echo json_encode($borrowings);
    
} catch(PDOException $e) {
    // Clean output buffer and send error response
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Failed to fetch borrowings: ' . $e->getMessage()]);
}
?>

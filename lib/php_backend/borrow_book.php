<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$host = '192.168.0.9';
$db = 'library_db';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['anggota_id']) || !isset($data['book_id']) || !isset($data['borrow_date']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$anggotaId = $data['anggota_id'];
$bookId = $data['book_id'];
$borrowDate = $data['borrow_date'];
$password = $data['password'];

try {
    $conn->beginTransaction();
    
    // Verify user password
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE anggota_id = :anggota_id");
    $stmt->bindParam(':anggota_id', $anggotaId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }
    
    // Check if book exists and has stock
    $stmt = $conn->prepare("SELECT stock FROM books WHERE book_id = :book_id");
    $stmt->bindParam(':book_id', $bookId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }
    
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($book['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Book is out of stock']);
        exit;
    }
    
    // Calculate due date (14 days from borrow date)
    $dueDate = date('Y-m-d', strtotime($borrowDate . ' + 14 days'));
    
    // Insert borrowing record
    $stmt = $conn->prepare("INSERT INTO borrowings (anggota_id, book_id, borrow_date, due_date, status) 
                           VALUES (:anggota_id, :book_id, :borrow_date, :due_date, 'borrowed')");
    
    $stmt->bindParam(':anggota_id', $anggotaId);
    $stmt->bindParam(':book_id', $bookId);
    $stmt->bindParam(':borrow_date', $borrowDate);
    $stmt->bindParam(':due_date', $dueDate);
    
    $stmt->execute();
    $borrowingId = $conn->lastInsertId();
    
    // Update book stock
    $stmt = $conn->prepare("UPDATE books SET stock = stock - 1 WHERE book_id = :book_id");
    $stmt->bindParam(':book_id', $bookId);
    $stmt->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Book borrowed successfully. Due date: ' . $dueDate,
        'borrowing_id' => $borrowingId,
        'due_date' => $dueDate
    ]);
    
} catch(PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to borrow book: ' . $e->getMessage()]);
}
?>

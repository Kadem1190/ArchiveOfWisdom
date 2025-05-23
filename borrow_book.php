<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

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
    $pdo->beginTransaction();
    
    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE anggota_id = ?");
    $stmt->execute([$anggotaId]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($password !== $user['password']) {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }
    
    // Check book stock
    $stmt = $pdo->prepare("SELECT stock FROM books WHERE book_id = ?");
    $stmt->execute([$bookId]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }
    
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($book['stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Book is out of stock']);
        exit;
    }
    
    // Calculate due date
    $dueDate = date('Y-m-d', strtotime($borrowDate . ' + 14 days'));
    
    // Insert borrowing
    $stmt = $pdo->prepare("INSERT INTO borrowings (anggota_id, book_id, borrow_date, due_date, status) 
                           VALUES (?, ?, ?, ?, 'borrowed')");
    
    $stmt->execute([$anggotaId, $bookId, $borrowDate, $dueDate]);
    $borrowingId = $pdo->lastInsertId();
    
    // Update stock
    $stmt = $pdo->prepare("UPDATE books SET stock = stock - 1 WHERE book_id = ?");
    $stmt->execute([$bookId]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Book borrowed successfully. Due date: ' . $dueDate,
        'borrowing_id' => $borrowingId,
        'due_date' => $dueDate
    ]);
    
} catch(PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to borrow book: ' . $e->getMessage()]);
}
?>
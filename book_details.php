<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if (!isset($_GET['book_id'])) {
    echo json_encode(['success' => false, 'message' => 'Book ID is required']);
    exit;
}

$bookId = $_GET['book_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->execute([$bookId]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }
    
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (isset($book['cover']) && $book['cover'] !== null) {
        $book['cover_url'] = 'data:image/jpeg;base64,' . base64_encode($book['cover']);
    } else {
        $book['cover_url'] = null;
    }
    unset($book['cover']);
    
    echo json_encode($book);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch book details: ' . $e->getMessage()]);
}
?>
<?php
// Start output buffering
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
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    // Get all books
    $stmt = $conn->prepare("SELECT * FROM books ORDER BY title");
    $stmt->execute();
    
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert cover BLOB to base64 URL
    foreach ($books as &$book) {
        if (isset($book['cover']) && $book['cover'] !== null) {
            $book['cover_url'] = 'data:image/jpeg;base64,' . base64_encode($book['cover']);
        } else {
            $book['cover_url'] = null;
        }
        unset($book['cover']); // Remove the BLOB data from response
    }
    
    ob_end_clean();
    echo json_encode($books);
    
} catch(PDOException $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Failed to fetch books: ' . $e->getMessage()]);
}
?>

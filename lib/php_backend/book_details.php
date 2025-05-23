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

// Get book_id from request
if (!isset($_GET['book_id'])) {
    // Clean output buffer and send error response
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Book ID is required']);
    exit;
}

$bookId = $_GET['book_id'];

try {
    // Get book details
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = :book_id");
    $stmt->bindParam(':book_id', $bookId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Clean output buffer and send error response
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }
    
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ensure numeric fields are properly typed
    $book['book_id'] = (int)$book['book_id'];
    $book['stock'] = (int)$book['stock'];
    if (isset($book['year_published']) && $book['year_published'] !== null) {
        $book['year_published'] = (int)$book['year_published'];
    }
    
    // Handle cover image
    if (isset($book['cover']) && $book['cover'] !== null && !empty($book['cover'])) {
        // Check if it's already base64 encoded or raw binary
        $coverData = $book['cover'];
        
        // If it's binary data, encode it
        if (!base64_decode($coverData, true)) {
            // It's raw binary data, encode it
            $base64Cover = base64_encode($coverData);
        } else {
            // It might already be base64, use as is
            $base64Cover = $coverData;
        }
        
        // Create data URL
        $book['cover_url'] = 'data:image/jpeg;base64,' . $base64Cover;
    } else {
        $book['cover_url'] = null;
    }
    
    // Remove the original cover BLOB from response
    unset($book['cover']);
    
    // Clean output buffer and send JSON response
    ob_end_clean();
    echo json_encode($book);
    
} catch(PDOException $e) {
    // Clean output buffer and send error response
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Failed to fetch book details: ' . $e->getMessage()]);
}
?>

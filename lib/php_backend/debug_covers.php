<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$host = 'localhost';
$db = 'db_perpustakaan';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT book_id, title, 
                           CASE 
                               WHEN cover IS NULL THEN 'NULL'
                               WHEN cover = '' THEN 'EMPTY'
                               ELSE CONCAT('BLOB_SIZE:', LENGTH(cover))
                           END as cover_status,
                           CASE 
                               WHEN cover IS NOT NULL AND cover != '' THEN 
                                   SUBSTRING(HEX(cover), 1, 20)
                               ELSE NULL
                           END as cover_hex_preview
                           FROM books 
                           ORDER BY book_id");
    $stmt->execute();
    
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total_books' => count($books),
        'books' => $books
    ], JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
}
?>

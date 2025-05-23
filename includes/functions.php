<?php
/**
 * Log activity to the logs table
 */
function logActivity($details, $userId = null, $tableName = null, $operation = null, $recordId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (table_name, operation, record_id, user_id, details, log_time) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$tableName, $operation, $recordId, $userId, $details]);
        return true;
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT u.*, a.* FROM users u 
                              JOIN anggota a ON u.anggota_id = a.anggota_id 
                              WHERE u.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting user: " . $e->getMessage());
        return false;
    }
}

/**
 * Get admin by ID
 */
function getAdminById($adminId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
        $stmt->execute([$adminId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting admin: " . $e->getMessage());
        return false;
    }
}

/**
 * Get book by ID
 */
function getBookById($bookId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
        $stmt->execute([$bookId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting book: " . $e->getMessage());
        return false;
    }
}

/**
 * Get borrowing by ID
 */
function getBorrowingById($borrowingId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT b.*, a.name as anggota_name, bk.title as book_title 
                              FROM borrowings b
                              JOIN anggota a ON b.anggota_id = a.anggota_id
                              JOIN books bk ON b.book_id = bk.book_id
                              WHERE b.borrowing_id = ?");
        $stmt->execute([$borrowingId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting borrowing: " . $e->getMessage());
        return false;
    }
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Calculate days remaining or overdue
 */
function calculateDaysRemaining($dueDate) {
    $now = new DateTime();
    $due = new DateTime($dueDate);
    $diff = $now->diff($due);
    
    if ($now > $due) {
        return -$diff->days; // Negative number for overdue
    } else {
        return $diff->days; // Positive number for days remaining
    }
}

/**
 * Generate pagination links
 */
function generatePagination($currentPage, $totalPages, $urlPattern) {
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $currentPage - 1) . '">&laquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, 1) . '">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $i) . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $totalPages) . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $currentPage + 1) . '">&raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Upload image file
 */
function uploadImage($file, $targetDir = UPLOAD_DIR) {
    // Check if directory exists, create if not
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $targetFile = $targetDir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ["success" => false, "message" => "File is not an image."];
    }
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "File is too large. Maximum size is 5MB."];
    }
    
    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return ["success" => false, "message" => "Only JPG, JPEG, PNG files are allowed."];
    }
    
    // Generate unique filename
    $newFilename = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $newFilename;
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ["success" => true, "filename" => $newFilename, "path" => $targetFile];
    } else {
        return ["success" => false, "message" => "There was an error uploading your file."];
    }
}

/**
 * Check if a borrowing is overdue
 */
function isOverdue($dueDate) {
    return strtotime($dueDate) < strtotime(date('Y-m-d'));
}

/**
 * Update overdue borrowings
 */
function updateOverdueBorrowings() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE borrowings 
                              SET status = 'overdue' 
                              WHERE status = 'borrowed' 
                              AND due_date < CURDATE()");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error updating overdue borrowings: " . $e->getMessage());
        return false;
    }
}

/**
 * Get total counts for dashboard
 */
function getDashboardCounts() {
    global $pdo;
    
    try {
        $counts = [];
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
        $counts['users'] = $stmt->fetch()['count'];
        
        // Total books
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
        $counts['books'] = $stmt->fetch()['count'];
        
        // Total borrowings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings");
        $counts['borrowings'] = $stmt->fetch()['count'];
        
        // Active borrowings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'");
        $counts['active_borrowings'] = $stmt->fetch()['count'];
        
        // Overdue borrowings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrowings WHERE status = 'overdue'");
        $counts['overdue_borrowings'] = $stmt->fetch()['count'];
        
        // Pending registrations
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'inactive' AND role = 'user'");
        $counts['pending_registrations'] = $stmt->fetch()['count'];
        
        return $counts;
    } catch (PDOException $e) {
        error_log("Error getting dashboard counts: " . $e->getMessage());
        return [
            'users' => 0,
            'books' => 0,
            'borrowings' => 0,
            'active_borrowings' => 0,
            'overdue_borrowings' => 0,
            'pending_registrations' => 0
        ];
    }
}

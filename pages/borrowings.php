<?php
$pageTitle = 'Borrowings Management';

// Process actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $borrowingId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($action === 'return' && $borrowingId > 0) {
        try {
            // Get borrowing details
            $stmt = $pdo->prepare("SELECT * FROM borrowings WHERE borrowing_id = ?");
            $stmt->execute([$borrowingId]);
            $borrowing = $stmt->fetch();
            
            if (!$borrowing) {
                $errorMessage = "Borrowing record not found.";
            } else {
                // Update borrowing status
                $stmt = $pdo->prepare("UPDATE borrowings SET status = 'returned', return_date = CURDATE() WHERE borrowing_id = ?");
                $stmt->execute([$borrowingId]);
                
                // Update book stock
                $stmt = $pdo->prepare("UPDATE books SET stock = stock + 1 WHERE book_id = ?");
                $stmt->execute([$borrowing['book_id']]);
                
                logActivity('Borrowing marked as returned', $_SESSION['admin_id'], 'borrowings', 'UPDATE', $borrowingId);
                
                $successMessage = "Book has been marked as returned successfully.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error processing return: " . $e->getMessage();
        }
    } elseif ($action === 'view' && $borrowingId > 0) {
        // Get borrowing details
        try {
            $stmt = $pdo->prepare("SELECT b.*, a.name as anggota_name, a.nim, bk.title as book_title, bk.author as book_author 
                                  FROM borrowings b
                                  JOIN anggota a ON b.anggota_id = a.anggota_id
                                  JOIN books bk ON b.book_id = bk.book_id
                                  WHERE b.borrowing_id = ?");
            $stmt->execute([$borrowingId]);
            $borrowing = $stmt->fetch();
            
            if (!$borrowing) {
                $errorMessage = "Borrowing record not found.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error retrieving borrowing details: " . $e->getMessage();
        }
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $anggotaId = intval($_POST['anggota_id']);
        $bookId = intval($_POST['book_id']);
        $borrowDate = $_POST['borrow_date'];
        $dueDate = $_POST['due_date'];
        
        if (empty($anggotaId) || empty($bookId) || empty($borrowDate) || empty($dueDate)) {
            $errorMessage = "All fields are required.";
        } else {
            try {
                // Check if book is available
                $stmt = $pdo->prepare("SELECT stock FROM books WHERE book_id = ?");
                $stmt->execute([$bookId]);
                $book = $stmt->fetch();
                
                if (!$book) {
                    $errorMessage = "Book not found.";
                } elseif ($book['stock'] <= 0) {
                    $errorMessage = "Book is out of stock.";
                } else {
                    // Insert borrowing record
                    $stmt = $pdo->prepare("INSERT INTO borrowings (anggota_id, book_id, borrow_date, due_date, status) 
                                          VALUES (?, ?, ?, ?, 'borrowed')");
                    $stmt->execute([$anggotaId, $bookId, $borrowDate, $dueDate]);
                    
                    $borrowingId = $pdo->lastInsertId();
                    
                    // Update book stock
                    $stmt = $pdo->prepare("UPDATE books SET stock = stock - 1 WHERE book_id = ?");
                    $stmt->execute([$bookId]);
                    
                    logActivity('Borrowing added', $_SESSION['admin_id'], 'borrowings', 'INSERT', $borrowingId);
                    
                    $successMessage = "Borrowing has been added successfully.";
                }
            } catch (PDOException $e) {
                $errorMessage = "Error adding borrowing: " . $e->getMessage();
            }
        }
    }
}

// Update overdue borrowings
updateOverdueBorrowings();

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query based on filters
$query = "SELECT b.*, a.name as anggota_name, a.nim, bk.title as book_title 
          FROM borrowings b
          JOIN anggota a ON b.anggota_id = a.anggota_id
          JOIN books bk ON b.book_id = bk.book_id";
$countQuery = "SELECT COUNT(*) as total FROM borrowings b
               JOIN anggota a ON b.anggota_id = a.anggota_id
               JOIN books bk ON b.book_id = bk.book_id";
$params = [];

if ($filter === 'borrowed') {
    $query .= " WHERE b.status = 'borrowed'";
    $countQuery .= " WHERE b.status = 'borrowed'";
} elseif ($filter === 'returned') {
    $query .= " WHERE b.status = 'returned'";
    $countQuery .= " WHERE b.status = 'returned'";
} elseif ($filter === 'overdue') {
    $query .= " WHERE b.status = 'overdue'";
    $countQuery .= " WHERE b.status = 'overdue'";
}

if (!empty($search)) {
    $whereClause = " WHERE ";
    if ($filter) {
        $whereClause = " AND ";
    }
    
    $query .= $whereClause . "(a.name LIKE ? OR a.nim LIKE ? OR bk.title LIKE ?)";
    $countQuery .= $whereClause . "(a.name LIKE ? OR a.nim LIKE ? OR bk.title LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
}

// Get total count
try {
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalItems = $stmt->fetch()['total'];
    $totalPages = ceil($totalItems / $limit);
} catch (PDOException $e) {
    $errorMessage = "Error counting borrowings: " . $e->getMessage();
}

// Get borrowings with pagination
$query .= " ORDER BY b.borrow_date DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $borrowings = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Error retrieving borrowings: " . $e->getMessage();
}

// Get members for dropdown
try {
    $stmt = $pdo->prepare("SELECT a.anggota_id, a.name, a.nim FROM anggota a
                          JOIN users u ON a.anggota_id = u.anggota_id
                          WHERE u.status = 'active' AND u.registration_status = 'approved'
                          ORDER BY a.name");
    $stmt->execute();
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Error retrieving members: " . $e->getMessage();
}

// Get books for dropdown
try {
    $stmt = $pdo->prepare("SELECT book_id, title, author, stock FROM books WHERE stock > 0 ORDER BY title");
    $stmt->execute();
    $availableBooks = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Error retrieving books: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Borrowings Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="index.php?page=borrowings" class="btn btn-sm btn-outline-secondary <?php echo empty($filter) ? 'active' : ''; ?>">All</a>
            <a href="index.php?page=borrowings&filter=borrowed" class="btn btn-sm btn-outline-info <?php echo $filter === 'borrowed' ? 'active' : ''; ?>">Borrowed</a>
            <a href="index.php?page=borrowings&filter=returned" class="btn btn-sm btn-outline-success <?php echo $filter === 'returned' ? 'active' : ''; ?>">Returned</a>
            <a href="index.php?page=borrowings&filter=overdue" class="btn btn-sm btn-outline-danger <?php echo $filter === 'overdue' ? 'active' : ''; ?>">Overdue</a>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addBorrowingModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Borrowing
        </button>
    </div>
</div>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success"><?php echo $successMessage; ?></div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<?php if (isset($borrowing) && isset($action) && $action === 'view'): ?>
<!-- Borrowing Details -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">Borrowing Details</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted">Member Information</h6>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Name</th>
                        <td><?php echo htmlspecialchars($borrowing['anggota_name']); ?></td>
                    </tr>
                    <tr>
                        <th>NIM</th>
                        <td><?php echo htmlspecialchars($borrowing['nim']); ?></td>
                    </tr>
                </table>
                
                <h6 class="text-muted mt-4">Book Information</h6>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Title</th>
                        <td><?php echo htmlspecialchars($borrowing['book_title']); ?></td>
                    </tr>
                    <tr>
                        <th>Author</th>
                        <td><?php echo htmlspecialchars($borrowing['book_author'] ?? 'N/A'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">Borrowing Information</h6>
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Borrow Date</th>
                        <td><?php echo formatDate($borrowing['borrow_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Due Date</th>
                        <td>
                            <?php echo formatDate($borrowing['due_date']); ?>
                            <?php if ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue'): ?>
                                <?php $daysRemaining = calculateDaysRemaining($borrowing['due_date']); ?>
                                <?php if ($daysRemaining < 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo abs($daysRemaining); ?> days overdue</span>
                                <?php elseif ($daysRemaining <= 3): ?>
                                    <span class="badge bg-warning ms-2"><?php echo $daysRemaining; ?> days remaining</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-2"><?php echo $daysRemaining; ?> days remaining</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Return Date</th>
                        <td><?php echo $borrowing['return_date'] ? formatDate($borrowing['return_date']) : 'Not returned yet'; ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-<?php 
                                echo $borrowing['status'] == 'borrowed' ? 'info' : 
                                    ($borrowing['status'] == 'returned' ? 'success' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($borrowing['status']); ?>
                            </span>
                        </td>
                    </tr>
                </table>
                
                <?php if ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue'): ?>
                    <div class="mt-4">
                        <a href="index.php?page=borrowings&action=return&id=<?php echo $borrowing['borrowing_id']; ?>" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i> Mark as Returned
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="index.php?page=borrowings<?php echo !empty($filter) ? '&filter=' . $filter : ''; ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1"></i> Back to Borrowings
        </a>
    </div>
</div>
<?php else: ?>
<!-- Search Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="index.php" method="get" class="row g-3">
            <input type="hidden" name="page" value="borrowings">
            <?php if (!empty($filter)): ?>
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <?php endif; ?>
            
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Search by member name, NIM, or book title" value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php?page=borrowings<?php echo !empty($filter) ? '&filter=' . $filter : ''; ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Borrowings Table -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">
            <?php
            if ($filter === 'borrowed') {
                echo 'Borrowed Books';
            } elseif ($filter === 'returned') {
                echo 'Returned Books';
            } elseif ($filter === 'overdue') {
                echo 'Overdue Books';
            } else {
                echo 'All Borrowings';
            }
            ?>
            <span class="badge bg-secondary ms-2"><?php echo $totalItems; ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($borrowings)): ?>
            <div class="p-4 text-center text-muted">No borrowings found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Member</th>
                            <th>Book</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowings as $borrowing): ?>
                            <tr>
                                <td><?php echo $borrowing['borrowing_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($borrowing['anggota_name']); ?>
                                    <div class="small text-muted"><?php echo htmlspecialchars($borrowing['nim']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($borrowing['book_title']); ?></td>
                                <td><?php echo formatDate($borrowing['borrow_date']); ?></td>
                                <td>
                                    <?php echo formatDate($borrowing['due_date']); ?>
                                    <?php if ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue'): ?>
                                        <?php $daysRemaining = calculateDaysRemaining($borrowing['due_date']); ?>
                                        <?php if ($daysRemaining < 0): ?>
                                            <div class="small text-danger"><?php echo abs($daysRemaining); ?> days overdue</div>
                                        <?php elseif ($daysRemaining <= 3): ?>
                                            <div class="small text-warning"><?php echo $daysRemaining; ?> days remaining</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $borrowing['return_date'] ? formatDate($borrowing['return_date']) : '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $borrowing['status'] == 'borrowed' ? 'info' : 
                                            ($borrowing['status'] == 'returned' ? 'success' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($borrowing['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="index.php?page=borrowings&action=view&id=<?php echo $borrowing['borrowing_id']; ?>" class="btn btn-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($borrowing['status'] === 'borrowed' || $borrowing['status'] === 'overdue'): ?>
                                            <a href="index.php?page=borrowings&action=return&id=<?php echo $borrowing['borrowing_id']; ?>" class="btn btn-success" title="Mark as Returned">
                                                <i class="bi bi-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white">
            <?php
            $urlPattern = "index.php?page=borrowings" . 
                (!empty($filter) ? "&filter=$filter" : "") . 
                (!empty($search) ? "&search=$search" : "") . 
                "&p=%d";
            echo generatePagination($page, $totalPages, $urlPattern);
            ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Add Borrowing Modal -->
<div class="modal fade" id="addBorrowingModal" tabindex="-1" aria-labelledby="addBorrowingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="index.php?page=borrowings" method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addBorrowingModalLabel">Add New Borrowing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="anggota_id" class="form-label">Member <span class="text-danger">*</span></label>
                        <select class="form-select" id="anggota_id" name="anggota_id" required>
                            <option value="">Select Member</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo $member['anggota_id']; ?>">
                                    <?php echo htmlspecialchars($member['name'] . ' (' . $member['nim'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="book_id" class="form-label">Book <span class="text-danger">*</span></label>
                        <select class="form-select" id="book_id" name="book_id" required>
                            <option value="">Select Book</option>
                            <?php foreach ($availableBooks as $book): ?>
                                <option value="<?php echo $book['book_id']; ?>">
                                    <?php echo htmlspecialchars($book['title'] . 
                                        ($book['author'] ? ' by ' . $book['author'] : '') . 
                                        ' (Stock: ' . $book['stock'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="borrow_date" class="form-label">Borrow Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="borrow_date" name="borrow_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+' . DEFAULT_BORROW_DAYS . ' days')); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Borrowing</button>
                </div>
            </form>
        </div>
    </div>
</div>

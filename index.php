<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get current page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$role = $_SESSION['role'];

// Define allowed pages for each role
$allowedPages = [
    ROLE_ADMIN => ['dashboard', 'users', 'books', 'borrowings', 'statistics', 'profile'],
    ROLE_STAFF => ['dashboard', 'members', 'borrowings', 'books', 'profile'],
    ROLE_MEMBER => ['books', 'my-borrowings', 'profile']
];

// Check if user has access to the requested page
if (!isset($allowedPages[$role]) || !in_array($page, $allowedPages[$role])) {
    $page = 'dashboard';
}

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <?php 
                    $menu = getNavigationMenu();
                    foreach ($menu as $menuPage => $menuItem): 
                    ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == $menuPage ? 'active' : ''; ?>" 
                               href="index.php?page=<?php echo $menuPage; ?>">
                                <i class="bi bi-<?php echo $menuItem['icon']; ?> me-2"></i>
                                <?php echo $menuItem['title']; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Account</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page == 'profile' ? 'active' : ''; ?>" href="index.php?page=profile">
                            <i class="bi bi-person-circle me-2"></i>
                            My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php
            // Load page content
            $pageFile = "pages/{$page}.php";
            
            // If page doesn't exist, show 404
            if (!file_exists($pageFile)) {
                $pageFile = "pages/404.php";
            }
            
            include $pageFile;
            ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: login.php');
    exit;
}

// Get current page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php if (isset($_SESSION['admin_id'])): ?>
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'users' ? 'active' : ''; ?>" href="index.php?page=users">
                                <i class="bi bi-people me-2"></i>
                                User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'books' ? 'active' : ''; ?>" href="index.php?page=books">
                                <i class="bi bi-book me-2"></i>
                                Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'borrowings' ? 'active' : ''; ?>" href="index.php?page=borrowings">
                                <i class="bi bi-bookmark me-2"></i>
                                Borrowings
                            </a>
                        </li>
                         <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'statistics' ? 'active' : ''; ?>" href="index.php?page=statistics">
                                <i class="bi bi-bar-chart me-2"></i>
                                Statistics
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Account</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <!-- <li class="nav-item">
                            <a class="nav-link  href="index.php?page=profile">
                                <i class="bi bi-person-circle me-2"></i>
                                My Profile
                            </a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        <?php endif; ?>

        <!-- Main content -->
        <main class="<?php echo isset($_SESSION['admin_id']) ? 'col-md-9 ms-sm-auto col-lg-10 px-md-4' : 'col-12'; ?>">
            <?php
            // Load page content
            if (isset($_SESSION['admin_id']) || basename($_SERVER['PHP_SELF']) == 'login.php') {
                $file = 'pages/' . $page . '.php';
                if (file_exists($file)) {
                    include $file;
                } else {
                    include 'pages/404.php';
                }
            }
            ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

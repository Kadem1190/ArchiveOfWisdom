<?php
require_once 'config.php';
require_once 'functions.php';

// Define roles as constants

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['role'], $roles);
}

/**
 * Require login - but don't redirect if already on login page
 */
function requireLogin() {
    if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    if (!isLoggedIn()) {
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            header('Location: login.php');
            exit;
        }
        return;
    }
    
    if (!hasRole($role)) {
        header('Location: unauthorized.php');
        exit;
    }
}

/**
 * Login user
 */
function loginUser($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT u.*, a.name FROM users u 
                              LEFT JOIN anggota a ON u.anggota_id = a.anggota_id 
                              WHERE u.username = ? AND u.status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // Disable hashing: compare plain text password directly
        if ($user && $password === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['anggota_id'] = $user['anggota_id'];
            
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get navigation menu based on role
 */
function getNavigationMenu() {
    $role = $_SESSION['role'] ?? '';
    $menu = [];
    
    switch ($role) {
        case ROLE_ADMIN:
            $menu = [
                'dashboard' => ['icon' => 'speedometer2', 'title' => 'Dashboard'],
                'users' => ['icon' => 'people', 'title' => 'User Management'],
                'books' => ['icon' => 'book', 'title' => 'Books'],
                'borrowings' => ['icon' => 'bookmark', 'title' => 'Borrowings'],
                'statistics' => ['icon' => 'bar-chart', 'title' => 'Statistics'],
            ];
            break;
            
        case ROLE_STAFF:
            $menu = [
                'dashboard' => ['icon' => 'speedometer2', 'title' => 'Dashboard'],
                'members' => ['icon' => 'person-plus', 'title' => 'Add Members'],
                'borrowings' => ['icon' => 'bookmark', 'title' => 'Borrowing Transactions'],
                'books' => ['icon' => 'book', 'title' => 'View Books'],
            ];
            break;
            
        case ROLE_MEMBER:
            $menu = [
                'books' => ['icon' => 'book', 'title' => 'Browse Books'],
                'my-borrowings' => ['icon' => 'bookmark', 'title' => 'My Borrowings'],
                'profile' => ['icon' => 'person-circle', 'title' => 'My Profile'],
            ];
            break;
    }
    
    return $menu;
}

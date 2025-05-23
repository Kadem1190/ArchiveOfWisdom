<?php
// Database configuration
$host = '192.168.0.9';
$dbname = 'library_db';
$username = 'root';
$password = '';

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Select the database
    $pdo->exec("USE `$dbname`");
    
    // Create anggota table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `anggota` (
        `anggota_id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `nim` varchar(20) NOT NULL,
        `class` varchar(50) NOT NULL,
        `address` text DEFAULT NULL,
        `place_of_birth` varchar(100) DEFAULT NULL,
        `date_of_birth` date NOT NULL,
        `gender` enum('Male','Female') NOT NULL,
        PRIMARY KEY (`anggota_id`),
        UNIQUE KEY `nim` (`nim`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `user_id` int(11) NOT NULL AUTO_INCREMENT,
        `anggota_id` int(11) NOT NULL,
        `username` varchar(50) NOT NULL,
        `password_hash` varchar(255) NOT NULL,
        `full_name` varchar(100) DEFAULT NULL,
        `role` enum('admin','user') NOT NULL DEFAULT 'user',
        `status` enum('active','inactive','banned') NOT NULL DEFAULT 'inactive',
        `registration_status` enum('pending','approved') NOT NULL DEFAULT 'pending',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`user_id`),
        UNIQUE KEY `username` (`username`),
        KEY `anggota_id` (`anggota_id`),
        CONSTRAINT `users_ibfk_1` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`anggota_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create books table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `books` (
        `book_id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `author` varchar(100) DEFAULT NULL,
        `publisher` varchar(100) DEFAULT NULL,
        `year_published` int(4) DEFAULT NULL,
        `cover` longblob DEFAULT NULL,
        `stock` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`book_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create borrowings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `borrowings` (
        `borrowing_id` int(11) NOT NULL AUTO_INCREMENT,
        `anggota_id` int(11) NOT NULL,
        `book_id` int(11) NOT NULL,
        `borrow_date` date NOT NULL,
        `due_date` date DEFAULT NULL,
        `return_date` date DEFAULT NULL,
        `status` enum('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
        PRIMARY KEY (`borrowing_id`),
        KEY `anggota_id` (`anggota_id`),
        KEY `book_id` (`book_id`),
        CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`anggota_id`) ON DELETE CASCADE,
        CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `logs` (
        `log_id` int(11) NOT NULL AUTO_INCREMENT,
        `table_name` varchar(50) DEFAULT NULL,
        `operation` varchar(20) DEFAULT NULL,
        `record_id` int(11) DEFAULT NULL,
        `user_id` int(11) DEFAULT NULL,
        `details` text NOT NULL,
        `log_time` datetime NOT NULL,
        PRIMARY KEY (`log_id`),
        KEY `user_id` (`user_id`),
        KEY `table_operation` (`table_name`,`operation`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn() > 0;
    
    // Create default admin user if none exists
    if (!$adminExists) {
        // Create admin anggota record
        $stmt = $pdo->prepare("INSERT INTO anggota (name, nim, class, gender, date_of_birth) 
                              VALUES ('Admin User', 'ADMIN', 'ADMIN', 'Male', CURDATE())");
        $stmt->execute();
        $anggotaId = $pdo->lastInsertId();
        
        // Create admin user
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (anggota_id, username, password_hash, full_name, role, status, registration_status) 
                              VALUES (?, 'admin', ?, 'Admin User', 'admin', 'active', 'approved')");
        $stmt->execute([$anggotaId, $passwordHash]);
    }
    
    echo '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">';
    echo '<h1 style="color: #4CAF50; text-align: center;">Installation Successful</h1>';
    echo '<p style="font-size: 16px; line-height: 1.5;">The Library Management System database has been successfully installed.</p>';
    
    if (!$adminExists) {
        echo '<div style="background-color: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0;">';
        echo '<h2 style="color: #2E7D32; margin-top: 0;">Default Admin Credentials</h2>';
        echo '<p><strong>Username:</strong> admin</p>';
        echo '<p><strong>Password:</strong> admin123</p>';
        echo '<p style="color: #f44336;"><strong>Important:</strong> Please change this password after your first login!</p>';
        echo '</div>';
    }
    
    echo '<div style="text-align: center; margin-top: 30px;">';
    echo '<a href="login.php" style="display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">Go to Login Page</a>';
    echo '</div>';
    echo '</div>';
    
} catch (PDOException $e) {
    echo '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #f44336; border-radius: 5px; background-color: #ffebee;">';
    echo '<h1 style="color: #f44336; text-align: center;">Installation Failed</h1>';
    echo '<p style="font-size: 16px; line-height: 1.5;">An error occurred during installation:</p>';
    echo '<div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;">';
    echo '<p style="font-family: monospace;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    echo '<p>Please check your database configuration and try again.</p>';
    echo '</div>';
}
?>

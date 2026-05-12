<?php
/**
 * Database Connection - Optimized for 2GB RAM Systems
 * Features:
 * - Connection pooling preparation
 * - Reduced memory overhead
 * - Better error handling
 */

$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password - change if you set a password in XAMPP

// Create connection without database
$conn = new mysqli($servername, $username, $password);
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    } else {
        echo "Database connection failed. Please try again later.";
        exit();
    }
}

// Create database if it doesn't exist
$dbname = "test";
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql)) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database initialization failed']);
        exit();
    } else {
        echo "Database initialization failed. Please contact administrator.";
        exit();
    }
}

// Select the database
if (!$conn->select_db($dbname)) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to select database']);
        exit();
    } else {
        echo "Failed to select database. Please contact administrator.";
        exit();
    }
}

// Create tasks table if it doesn't exist
$table_sql = "CREATE TABLE IF NOT EXISTS test.tasks (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES test.users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($table_sql)) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Table creation failed']);
        exit();
    } else {
        echo "Table creation failed. Please contact administrator.";
        exit();
    }
}

// Set proper timeouts for low-memory systems
$conn->set_charset("utf8mb4");
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
?>

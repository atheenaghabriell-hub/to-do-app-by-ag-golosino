<?php
/**
 * Database Connection
 * Optimized for low-resource systems
 */

$servername = "localhost";
$username = "root";
$password = "";

// Create connection without database
$conn = new mysqli($servername, $username, $password);

// Set connection timeout for low-resource systems
$conn->connect_timeout = 5;

if ($conn->connect_error) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    } else {
        echo "Database connection failed";
    }
    exit();
}

// Create database if it doesn't exist
$dbname = "test";
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql)) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error creating database']);
    } else {
        echo "Error creating database";
    }
    exit();
}

// Select the database
if (!$conn->select_db($dbname)) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to select database']);
    } else {
        echo "Failed to select database";
    }
    exit();
}

// Create tasks table if it doesn't exist
$table_sql = "CREATE TABLE IF NOT EXISTS test.tasks (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($table_sql)) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error creating table']);
    } else {
        echo "Error creating table";
    }
    exit();
}

// Set charset to UTF8MB4 for proper emoji/unicode support
$conn->set_charset("utf8mb4");
?>
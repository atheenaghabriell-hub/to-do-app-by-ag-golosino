<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password - change if you set a password in XAMPP

// Create connection without database
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
        exit();
    } else {
        echo "Database connection failed: " . htmlspecialchars($conn->connect_error);
        exit();
    }
}

// Create database if it doesn't exist
$dbname = "test";
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // Database created successfully or already exists
} else {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error creating database: ' . $conn->error]);
        exit();
    } else {
        echo "Error creating database: " . htmlspecialchars($conn->error);
        exit();
    }
}

// Select the database
if (!$conn->select_db($dbname)) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to select database: ' . $conn->error]);
        exit();
    } else {
        echo "Failed to select database: " . htmlspecialchars($conn->error);
        exit();
    }
}

// Create tasks table if it doesn't exist
$table_sql = "CREATE TABLE IF NOT EXISTS test.tasks (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($table_sql) === TRUE) {
    // Table created successfully or already exists
} else {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error creating table: ' . $conn->error]);
        exit();
    } else {
        echo "Error creating table: " . htmlspecialchars($conn->error);
        exit();
    }
}
?>
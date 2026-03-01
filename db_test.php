<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password

// Create connection without database
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to MySQL successfully!<br>";

// Create database if it doesn't exist
$dbname = "test";
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database '$dbname' ready.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create tasks table if it doesn't exist
$table_sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($table_sql) === TRUE) {
    echo "Table 'tasks' ready.<br>";
} else {
    die("Error creating table: " . $conn->error);
}

// Check if there are tasks
$result = $conn->query("SELECT COUNT(*) as count FROM test.tasks");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Tasks in database: " . $row['count'] . "<br>";
} else {
    echo "Error checking tasks: " . $conn->error . "<br>";
}

$conn->close();
?>
<?php
// Database connection parameters for XAMPP
$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is empty
$dbname = "test"; // Change this to your database name if different

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header("Location: error.php?error=" . urlencode($conn->connect_error));
    exit();
}

echo "Connected successfully to XAMPP MySQL database!";

// Close connection
$conn->close();
?>
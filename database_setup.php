<?php
/**
 * Database Setup Script
 * Run this file ONCE to create the necessary tables
 * Access it in your browser: http://localhost/to-do-app-by-ag-golosino/database_setup.php
 */

include 'db.php';

header('Content-Type: text/html; charset=UTF-8');

$setup_complete = false;
$messages = [];

// Create users table
$sql_users = "CREATE TABLE IF NOT EXISTS test.users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql_users) === TRUE) {
    $messages[] = "✓ Users table created successfully";
} else {
    $messages[] = "✗ Error creating users table: " . $conn->error;
}

// Modify tasks table to include user_id
$sql_check_user_id = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME='tasks' AND COLUMN_NAME='user_id' AND TABLE_SCHEMA='test'";
$result = $conn->query($sql_check_user_id);

if ($result && $result->num_rows == 0) {
    // user_id column doesn't exist, add it
    $sql_alter_tasks = "ALTER TABLE test.tasks ADD COLUMN user_id INT NOT NULL DEFAULT 0 AFTER id, 
                        ADD FOREIGN KEY (user_id) REFERENCES test.users(id) ON DELETE CASCADE,
                        ADD INDEX idx_user_id (user_id)";

    if ($conn->query($sql_alter_tasks) === TRUE) {
        $messages[] = "✓ user_id column added to tasks table";
    } else {
        $messages[] = "✗ Error modifying tasks table: " . $conn->error;
    }
} else {
    $messages[] = "✓ Tasks table already has user_id column";
}

$setup_complete = true;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - To-Do App</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }

        .message {
            margin: 10px 0;
            padding: 10px;
            border-left: 4px solid #28a745;
            background-color: #f0f8f4;
        }

        .message.error {
            border-left-color: #dc3545;
            background-color: #fef5f5;
            color: #721c24;
        }

        .success-info {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }

        .action-links {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }

        .action-links a {
            display: inline-block;
            margin-right: 10px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .action-links a:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Database Setup</h1>

        <?php foreach ($messages as $msg): ?>
            <div class="message <?php echo strpos($msg, '✗') === 0 ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endforeach; ?>

        <?php if ($setup_complete): ?>
            <div class="success-info">
                <h3>✓ Setup Complete!</h3>
                <p>Your database has been configured with the users table and tasks table modifications.</p>
                <p><strong>Next Steps:</strong></p>
                <ul>
                    <li>Navigate to the login page to create your first account</li>
                    <li>Register a new user with a username and password</li>
                    <li>Start managing your tasks!</li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="action-links">
            <a href="register.html">Register Account</a>
            <a href="login.html">Login</a>
            <a href="index.php">Go to App</a>
        </div>
    </div>
</body>

</html>
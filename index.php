<?php
/**
 * Main Application Entry Point
 * Handles both authenticated and unauthenticated views
 * Optimized for low-memory systems
 */

include 'auth_check.php';

$user = checkAuth() ? getCurrentUser() : false;
$is_logged_in = (bool)$user;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="notificationContainer"></div>
    <?php if ($is_logged_in): ?>
        <!-- User Info Bar -->
        <div class="user-bar">
            <div class="user-info">
                <span class="username">Welcome, <strong><?php echo htmlspecialchars($user['username']); ?></strong>!</span>
            </div>
            <div class="user-actions">
                <a href="tasks.php" class="nav-link">View All Tasks</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <h1>My To-Do List</h1>
        <?php if (!$is_logged_in): ?>
            <div style="text-align: center; padding: 20px;">
                <p>Please <a href="login.html">log in</a> to manage your tasks.</p>
            </div>
        <?php else: ?>
            <form id="taskForm">
                <input type="text" id="title" placeholder="Task Title" required>
                <textarea id="description" placeholder="Task Description"></textarea>
                <button type="submit">Add Task</button>
            </form>
            <div id="loading" style="display: none;">Loading tasks...</div>
            <ul id="taskList">
                <!-- Tasks will be loaded here -->
            </ul>
        <?php endif; ?>
    </div>
    <script src="script.js"></script>
</body>
</html>

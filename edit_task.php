<?php
/**
 * Edit Task Handler
 * Updates a task for the authenticated user
 * - Requires authentication
 * - Verifies user owns the task before updating
 * - Validates input
 * - Handles both AJAX and form submissions
 * - Returns JSON for AJAX, redirects for form submissions
 * - Protects against unauthorized access
 */

include 'auth_check.php';

// Check if user is authenticated
$user_id = checkAuth();
if (!$user_id) {
    header('Location: login.html');
    exit();
}

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tasks.php");
    exit();
}

$task_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');
}

// Validate input
if ($task_id <= 0) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
    } else {
        header("Location: tasks.php?error=Invalid task ID");
    }
    exit();
}

if (empty($title)) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'Task title is required']);
    } else {
        header("Location: tasks.php?error=Task title is required");
    }
    exit();
}

// Update task ONLY if it belongs to the logged-in user
// This prevents users from updating other users' tasks
$stmt = $conn->prepare("UPDATE test.tasks SET title = ?, description = ? WHERE id = ? AND user_id = ?");
if (!$stmt) {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    } else {
        header("Location: tasks.php?error=Database error");
    }
    exit();
}

$stmt->bind_param("ssii", $title, $description, $task_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Task was updated successfully
        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => 'Task updated']);
        } else {
            header("Location: tasks.php?success=Task updated");
        }
    } else {
        // Task not found or doesn't belong to user
        if ($isAjax) {
            echo json_encode(['success' => false, 'error' => 'Task not found or permission denied']);
        } else {
            header("Location: tasks.php?error=Task not found or permission denied");
        }
    }
} else {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update task']);
    } else {
        header("Location: tasks.php?error=Failed to update task");
    }
}

$stmt->close();
$conn->close();
?>
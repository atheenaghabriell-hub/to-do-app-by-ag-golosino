<?php
/**
 * Delete Task Handler
 * Deletes a task for the authenticated user
 * - Requires authentication
 * - Verifies user owns the task before deletion
 * - Handles both AJAX and form submissions
 * - Returns JSON for AJAX, redirects for form submissions
 */

include 'auth_check.php';

// Check if user is authenticated
$user_id = checkAuth();
if (!$user_id) {
    // Redirect to login if not authenticated
    header('Location: login.html');
    exit();
}

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect to tasks page if not POST
    header('Location: tasks.php');
    exit();
}

$task_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json');
}

if ($task_id <= 0) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
    } else {
        header("Location: tasks.php?error=Invalid task ID");
    }
    exit();
}

// Delete task ONLY if it belongs to the logged-in user
// This prevents users from deleting other users' tasks
$stmt = $conn->prepare("DELETE FROM test.tasks WHERE id = ? AND user_id = ?");
if (!$stmt) {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    } else {
        header("Location: tasks.php?error=Database error");
    }
    exit();
}

$stmt->bind_param("ii", $task_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Task was deleted successfully
        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => 'Task deleted']);
        } else {
            header("Location: tasks.php?success=Task deleted");
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
        echo json_encode(['success' => false, 'error' => 'Failed to delete task']);
    } else {
        header("Location: tasks.php?error=Failed to delete task");
    }
}

$stmt->close();
$conn->close();
?>
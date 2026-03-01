<?php
/**
 * Toggle Task Status Handler
 * Changes task status between 'pending' and 'completed'
 * - Requires authentication
 * - Verifies user owns the task
 * - Returns JSON response
 */

include 'auth_check.php';

header('Content-Type: application/json');

// Check if user is authenticated
$user_id = checkAuth();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in']);
    exit();
}

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$task_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validate input
if ($task_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
    exit();
}

if (!in_array($status, ['pending', 'completed'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit();
}

// Update task status ONLY if it belongs to the logged-in user
$stmt = $conn->prepare("UPDATE test.tasks SET status = ? WHERE id = ? AND user_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

$stmt->bind_param("sii", $status, $task_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Task status updated']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Task not found or permission denied']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update task']);
}

$stmt->close();
$conn->close();
?>
<?php
/**
 * Add Task Handler
 * Creates a new task for the authenticated user
 * - Requires login
 * - Associates task with user_id
 */

include 'auth_check.php';

header('Content-Type: application/json');

// Check if user is authenticated
$user_id = checkAuth();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to add tasks']);
    exit();
}

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Validate input
if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Task title is required']);
    exit();
}

// Insert task for the logged-in user
// Note: user_id is included in the INSERT statement
$stmt = $conn->prepare("INSERT INTO test.tasks (user_id, title, description, status) VALUES (?, ?, ?, 'pending')");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iss", $user_id, $title, $description);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'task_id' => $stmt->insert_id,
        'message' => 'Task added successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to add task: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
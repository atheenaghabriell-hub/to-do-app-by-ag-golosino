<?php
/**
 * Add Task Handler
 */

include 'auth_check.php';

header('Content-Type: application/json');

$user_id = checkAuth();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Title required']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO test.tasks (user_id, title, description, status) VALUES (?, ?, ?, 'pending')");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

$stmt->bind_param("iss", $user_id, $title, $description);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'task_id' => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to add task']);
}

$stmt->close();
$conn->close();
?>
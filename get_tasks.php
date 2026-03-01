<?php
/**
 * Get Tasks Handler
 * Retrieves all tasks for the authenticated user
 * - Requires login
 * - Filters tasks by user_id
 * - Returns tasks in order of creation
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

include 'auth_check.php';

header('Content-Type: application/json');

// Check if user is authenticated
$user_id = checkAuth();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to view tasks']);
    exit();
}

// Fetch only tasks belonging to the logged-in user
// WHERE clause ensures data isolation between users
$sql = "SELECT id, title, description, status, created_at FROM test.tasks 
        WHERE user_id = ? 
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    $conn->close();
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$tasks = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
}

echo json_encode($tasks);

$stmt->close();
$conn->close();
?>
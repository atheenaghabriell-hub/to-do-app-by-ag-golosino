<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

include 'db.php';

header('Content-Type: application/json');

$sql = "SELECT id, title, description, status, created_at FROM test.tasks ORDER BY created_at DESC";
$result = $conn->query($sql);

$tasks = [];
if ($result) {
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
    }
    echo json_encode($tasks);
} else {
    echo json_encode(['error' => 'Failed to fetch tasks: ' . $conn->error]);
}

$conn->close();
?>
<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (empty($title)) {
        echo json_encode(['success' => false, 'error' => 'Task title is required']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO test.tasks (title, description, status) VALUES (?, ?, 'pending')");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("ss", $title, $description);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add task: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
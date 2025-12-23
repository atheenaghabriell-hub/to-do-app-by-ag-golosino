<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (empty($title)) {
        header("Location: tasks.php?error=Task title is required");
        exit();
    }

    $stmt = $conn->prepare("UPDATE test.tasks SET title = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $title, $description, $id);

    if ($stmt->execute()) {
        header("Location: tasks.php");
    } else {
        header("Location: tasks.php?error=Failed to update task: " . $stmt->error);
    }

    $stmt->close();
} else {
    header("Location: tasks.php");
}

$conn->close();
?>
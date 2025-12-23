<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM test.tasks WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: tasks.php");
    } else {
        header("Location: tasks.php?error=Failed to delete task: " . $stmt->error);
    }

    $stmt->close();
} else {
    header("Location: tasks.php");
}

$conn->close();
?>
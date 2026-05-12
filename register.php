<?php
/**
 * User Registration Handler
 */

include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Username and password required']);
    exit();
}

if (strlen($username) < 3 || strlen($username) > 50) {
    echo json_encode(['success' => false, 'error' => 'Username must be 3-50 characters']);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
    echo json_encode(['success' => false, 'error' => 'Invalid username format']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit();
}

$stmt = $conn->prepare("SELECT id FROM test.users WHERE username = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Username already exists']);
    $stmt->close();
    exit();
}
$stmt->close();

$password_hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);

$stmt = $conn->prepare("INSERT INTO test.users (username, password_hash) VALUES (?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

$stmt->bind_param("ss", $username, $password_hash);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Please log in.',
        'user_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Registration failed']);
}

$stmt->close();
$conn->close();
?>
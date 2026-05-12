<?php
/**
 * User Login Handler
 */

include 'auth_check.php';

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

$stmt = $conn->prepare("SELECT id, username, password_hash FROM test.users WHERE username = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    $stmt->close();
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    $conn->close();
    exit();
}

$token = loginUser($user['id'], $user['username']);

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user_id' => $user['id'],
    'username' => $user['username'],
    'token' => $token
]);

$conn->close();
?>
<?php
/**
 * User Login Handler
 * Processes login form submission
 * - Validates credentials against database
 * - Verifies password hash
 * - Creates session token
 * - Returns success/error response
 */

include 'auth_check.php';

header('Content-Type: application/json');

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get input
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate input
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit();
}

// Find user by username
$stmt = $conn->prepare("SELECT id, username, password_hash FROM test.users WHERE username = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows === 0) {
    // User not found - use generic message for security
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    $stmt->close();
    $conn->close();
    exit();
}

// Get user data
$user = $result->fetch_assoc();
$stmt->close();

// Verify password hash
// password_verify() safely compares plain text password with bcrypt hash
if (!password_verify($password, $user['password_hash'])) {
    // Password incorrect
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    $conn->close();
    exit();
}

// Password is correct! Log in the user
$token = loginUser($user['id'], $user['username']);

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user_id' => $user['id'],
    'username' => $user['username'],
    'token' => $token
]);

$conn->close();
?>
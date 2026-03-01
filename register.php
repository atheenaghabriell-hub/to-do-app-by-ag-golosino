<?php
/**
 * User Registration Handler
 * Processes registration form submission
 * - Validates input
 * - Creates hash from password
 * - Stores user in database
 */

include 'db.php';

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

// Validate username format
if (strlen($username) < 3 || strlen($username) > 50) {
    echo json_encode(['success' => false, 'error' => 'Username must be between 3 and 50 characters']);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
    echo json_encode(['success' => false, 'error' => 'Username can only contain letters, numbers, underscores, and hyphens']);
    exit();
}

// Validate password
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long']);
    exit();
}

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM test.users WHERE username = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Username already exists. Please choose a different one']);
    $stmt->close();
    exit();
}
$stmt->close();

// Hash password using bcrypt (password_hash)
// Uses PASSWORD_DEFAULT which is currently bcrypt
// This automatically handles salt and cost factor
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert new user into database
$stmt = $conn->prepare("INSERT INTO test.users (username, password_hash) VALUES (?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ss", $username, $password_hash);

if ($stmt->execute()) {
    // Registration successful
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Please log in.',
        'user_id' => $stmt->insert_id
    ]);
} else {
    // Check for specific error
    if ($conn->errno == 1062) {
        // Duplicate entry
        echo json_encode(['success' => false, 'error' => 'This username is already registered']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Registration failed: ' . $stmt->error]);
    }
}

$stmt->close();
$conn->close();
?>
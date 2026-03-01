<?php
/**
 * Authentication Check Helper
 * Include this file at the top of any page that requires authentication
 * It will verify the session token and redirect to login if not authenticated
 */

include 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated
 * Returns user_id if authenticated, false otherwise
 */
function checkAuth()
{
    global $conn;

    // Check if session token exists
    if (!isset($_SESSION['token']) || !isset($_SESSION['user_id'])) {
        return false;
    }

    $token = $_SESSION['token'];
    $user_id = intval($_SESSION['user_id']);

    // Verify token is valid (basic check - in security tokens might be stored in DB)
    if (empty($token) || $user_id <= 0) {
        // Clear session
        session_destroy();
        return false;
    }

    return $user_id;
}

/**
 * Redirect to login if not authenticated
 * Use this function at the start of protected pages
 */
function requireAuth()
{
    if (!checkAuth()) {
        header('Location: login.html');
        exit();
    }
}

/**
 * Get current logged-in user information
 * Returns array with user_id and username, or false if not logged in
 */
function getCurrentUser()
{
    global $conn;

    if (!checkAuth()) {
        return false;
    }

    $user_id = intval($_SESSION['user_id']);

    // Get user details from database
    $stmt = $conn->prepare("SELECT id, username FROM test.users WHERE id = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    $stmt->close();
    return false;
}

/**
 * Login user by creating session
 * Called after successful credential verification
 */
function loginUser($user_id, $username)
{
    // Generate secure random token
    $token = bin2hex(random_bytes(32));

    // Store in session
    $_SESSION['user_id'] = intval($user_id);
    $_SESSION['username'] = $username;
    $_SESSION['token'] = $token;
    $_SESSION['login_time'] = time();

    return $token;
}

/**
 * Logout user by destroying session
 */
function logoutUser()
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

?>

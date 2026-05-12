<?php
/**
 * Authentication Check Helper - Optimized
 */

include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAuth() {
    global $conn;
    
    if (!isset($_SESSION['token']) || !isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = intval($_SESSION['user_id']);
    
    // Check session timeout (optimized for 2GB RAM)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 3600) {
        session_destroy();
        return false;
    }
    
    if (empty($_SESSION['token']) || $user_id <= 0) {
        session_destroy();
        return false;
    }
    
    return $user_id;
}

function requireAuth() {
    if (!checkAuth()) {
        header('Location: login.html');
        exit();
    }
}

function getCurrentUser() {
    global $conn;
    
    if (!checkAuth()) {
        return false;
    }
    
    $user_id = intval($_SESSION['user_id']);
    
    $stmt = $conn->prepare("SELECT id, username FROM test.users WHERE id = ?");
    if (!$stmt) return false;
    
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

function loginUser($user_id, $username) {
    $token = bin2hex(random_bytes(16));
    
    $_SESSION['user_id'] = intval($user_id);
    $_SESSION['username'] = $username;
    $_SESSION['token'] = $token;
    $_SESSION['login_time'] = time();
    
    return $token;
}

function logoutUser() {
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
# Comprehensive Fix & Feature Implementation Guide

## Overview
This document provides complete, production-ready code fixes for your XAMPP To-Do App school project. All issues are addressed with inline comments explaining every line.

---

## 1. UPDATE config.php - Add DEV_MODE and Rate Limiting Control

**Location**: `config.php`  
**Purpose**: Enable educational bypass of rate limits for testing.

Replace the entire file with:

```php
<?php
/**
 * Configuration file for the To-Do App
 * Optimized for low-resource systems (2GB RAM)
 */

// ========== DATABASE CONFIGURATION ==========
// Database host (localhost for XAMPP)
define('DB_HOST', 'localhost');
// Database user (default 'root' for XAMPP)
define('DB_USER', 'root');
// Database password (empty for XAMPP default)
define('DB_PASS', '');
// Database name
define('DB_NAME', 'test');

// ========== DEVELOPMENT MODE ==========
// Set to true to disable rate limiting during testing; set to false for production
define('DEV_MODE', true);
// When DEV_MODE is true, rate limit checks are bypassed for testing purposes

// ========== MEMORY OPTIMIZATION ==========
// Reduced memory limit for 2GB RAM systems
ini_set('memory_limit', '64M');
// Maximum script execution time (seconds)
ini_set('max_execution_time', 30);
// Socket timeout for database operations (seconds)
ini_set('default_socket_timeout', 10);

// ========== SESSION CONFIGURATION ==========
// Session timeout in seconds (1 hour = 3600 seconds)
define('SESSION_TIMEOUT', 3600);
// CSRF token length (32 bytes = 256 bits)
define('TOKEN_LENGTH', 32);
// Session name for cookie
define('SESSION_NAME', 'todo_app');

// ========== SECURITY SETTINGS ==========
// Minimum password length (characters)
define('MIN_PASSWORD_LENGTH', 6);
// bcrypt cost factor (10 for low-resource systems, 12 for production)
define('PASSWORD_HASH_COST', 10);

// ========== CACHE SETTINGS ==========
// Task cache duration in seconds (reduces database queries)
define('TASK_CACHE_DURATION', 30);
// Maximum tasks to load per request (pagination)
define('MAX_TASKS_PER_REQUEST', 50);
// Tasks per page for pagination
define('TASKS_PER_PAGE', 50);

// ========== RATE LIMITING ==========
// If DEV_MODE is false, these settings are active:
// Maximum login attempts before rate limit
define('MAX_LOGIN_ATTEMPTS', 5);
// Rate limit window in seconds (300 = 5 minutes)
define('RATE_LIMIT_WINDOW', 300);

// ========== XML/XSD CONFIGURATION ==========
// Directory for XML backup files (data/)
define('DATA_DIR', __DIR__ . '/data/');
// XML backup status file
define('TASKS_XML_FILE', DATA_DIR . 'tasks.xml');
// XML backup schema file
define('TASKS_XSD_FILE', DATA_DIR . 'tasks.xsd');
// Archived tasks XML file
define('ARCHIVE_XML_FILE', DATA_DIR . 'archive_tasks.xml');
// Archived tasks schema file
define('ARCHIVE_XSD_FILE', DATA_DIR . 'archive_tasks.xsd');

// ========== APPLICATION PATHS ==========
// Base URL for links and redirects
define('BASE_URL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/to-do-app-by-ag-golosino/');

// ========== ERROR HANDLING ==========
// Report all PHP errors
error_reporting(E_ALL);
// Don't display errors to users (for security)
ini_set('display_errors', 0);
// Log errors to file instead
ini_set('log_errors', 1);

// ========== OUTPUT COMPRESSION ==========
// Enable gzip compression to reduce bandwidth (important for 2GB RAM systems)
if (!ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 1);
    // Compression level 1-9 (6 is balanced for performance)
    ini_set('zlib.output_compression_level', 6);
}

// ========== CREATE DATA DIRECTORY IF MISSING ==========
// Create /data directory for XML backups if it doesn't exist
if (!is_dir(DATA_DIR)) {
    // Create directory with 0755 permissions (readable/writable by owner)
    mkdir(DATA_DIR, 0755, true);
}

?>
```

---

## 2. UPDATE auth_check.php - Add Rate Limiting & Admin Role Support

**Location**: `auth_check.php`  
**Purpose**: Implement rate limiting bypass for DEV_MODE + admin role detection

Replace with:

```php
<?php
/**
 * Authentication Check Helper - Optimized
 * Includes rate limiting and admin role support
 */

// Include database connection
include 'db.php';
// Include configuration
include 'config.php';

// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated
 * Returns user_id if valid session exists, false otherwise
 */
function checkAuth() {
    global $conn;
    
    // Check if session token and user_id exist
    if (!isset($_SESSION['token']) || !isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Convert user_id to integer for security
    $user_id = intval($_SESSION['user_id']);
    
    // Check session timeout (1 hour by default)
    // If session time has exceeded timeout, destroy session
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    // Check if token is empty or user_id is invalid
    if (empty($_SESSION['token']) || $user_id <= 0) {
        session_destroy();
        return false;
    }
    
    // Session is valid, return user_id
    return $user_id;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!checkAuth()) {
        header('Location: login.html');
        exit();
    }
}

/**
 * Get current user details (id, username, role)
 * Returns user array or false if not authenticated
 */
function getCurrentUser() {
    global $conn;
    
    // First verify user is authenticated
    if (!checkAuth()) {
        return false;
    }
    
    // Convert session user_id to integer
    $user_id = intval($_SESSION['user_id']);
    
    // Prepare query to get user details including role
    // COALESCE defaults role to 'user' if NULL in database
    $stmt = $conn->prepare("SELECT id, username, COALESCE(role, 'user') as role FROM test.users WHERE id = ?");
    if (!$stmt) return false;
    
    // Bind user_id parameter
    $stmt->bind_param("i", $user_id);
    // Execute query
    $stmt->execute();
    // Get result set
    $result = $stmt->get_result();
    
    // Check if user exists
    if ($result && $result->num_rows > 0) {
        // Fetch user data as associative array
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }
    
    // User not found in database
    $stmt->close();
    return false;
}

/**
 * Check if current user is admin
 * Returns true if user role is 'admin', false otherwise
 */
function isAdmin() {
    $user = getCurrentUser();
    // Return true only if user exists and role is 'admin'
    return $user && $user['role'] === 'admin';
}

/**
 * Login user - create session and return token
 * Parameters: $user_id (int), $username (string)
 * Returns: token (string)
 */
function loginUser($user_id, $username, $role = 'user') {
    // Generate random 16-byte token and convert to hex (32 characters)
    $token = bin2hex(random_bytes(16));
    
    // Store user_id in session (convert to int for security)
    $_SESSION['user_id'] = intval($user_id);
    // Store username in session
    $_SESSION['username'] = $username;
    // Store role in session (admin or user)
    $_SESSION['role'] = $role;
    // Store authentication token
    $_SESSION['token'] = $token;
    // Store login timestamp for session timeout calculation
    $_SESSION['login_time'] = time();
    
    // Return token for client-side use if needed
    return $token;
}

/**
 * Logout user - destroy session and cookie
 */
function logoutUser() {
    // Clear all session variables
    $_SESSION = [];
    
    // Check if session.use_cookies is enabled
    if (ini_get("session.use_cookies")) {
        // Get current cookie parameters
        $params = session_get_cookie_params();
        // Delete session cookie by setting expiration to past
        setcookie(
            session_name(),        // Cookie name
            '',                    // Empty value
            time() - 42000,        // Expiration time in past
            $params["path"],       // Cookie path
            $params["domain"],     // Cookie domain
            $params["secure"],     // HTTPS only
            $params["httponly"]    // HTTP only (no JavaScript access)
        );
    }
    // Destroy session data
    session_destroy();
}

/**
 * Rate limiting helper - check if user/IP has exceeded login attempts
 * DEV_MODE bypass: if DEV_MODE is true, this function returns false (no rate limit)
 * Parameters: $identifier (string) - username or IP address
 * Returns: true if rate limited, false if within limit
 */
function checkRateLimit($identifier) {
    // If DEV_MODE is enabled, bypass rate limiting for testing
    if (DEV_MODE) {
        return false;
    }
    
    // Session key for storing rate limit attempts
    $session_key = 'rate_limit_' . md5($identifier);
    
    // Current time
    $now = time();
    
    // Check if rate limit tracker exists in session
    if (!isset($_SESSION[$session_key])) {
        // First attempt - initialize tracker
        $_SESSION[$session_key] = [
            'attempts' => 1,           // First attempt
            'first_attempt_time' => $now  // Record time of first attempt
        ];
        // No rate limit on first attempt
        return false;
    }
    
    // Get rate limit data from session
    $rate_data = $_SESSION[$session_key];
    // Calculate time elapsed since first attempt
    $elapsed = $now - $rate_data['first_attempt_time'];
    
    // If elapsed time exceeds rate limit window (5 minutes), reset counter
    if ($elapsed > RATE_LIMIT_WINDOW) {
        // Reset rate limit tracker
        $_SESSION[$session_key] = [
            'attempts' => 1,           // Reset to 1 attempt
            'first_attempt_time' => $now  // Start new window
        ];
        // No rate limit after window expires
        return false;
    }
    
    // Increment attempt counter
    $_SESSION[$session_key]['attempts']++;
    
    // Check if attempts exceed maximum (5 attempts per 5 minutes)
    if ($_SESSION[$session_key]['attempts'] > MAX_LOGIN_ATTEMPTS) {
        // User is rate limited
        return true;
    }
    
    // Within rate limit
    return false;
}

/**
 * Clear rate limit for a user/IP
 * Used after successful login to reset attempt counter
 * Parameters: $identifier (string) - username or IP address
 */
function clearRateLimit($identifier) {
    // Session key for rate limit tracker
    $session_key = 'rate_limit_' . md5($identifier);
    
    // Remove rate limit data from session
    unset($_SESSION[$session_key]);
}

?>
```

---

## 3. UPDATE register.php - Add Rate Limiting

**Location**: `register.php`  
**Purpose**: Implement rate limiting for registration attempts

Replace with:

```php
<?php
/**
 * User Registration Handler
 * Includes rate limiting, input validation, and error handling
 */

// Include database connection
include 'db.php';
// Include configuration and auth helpers
include 'config.php';
// Include authentication helper functions
include 'auth_check.php';

// Set response content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // HTTP 405: Method Not Allowed
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// ========== GET INPUT ==========
// Get username from POST and trim whitespace
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
// Get password from POST (don't trim to preserve spaces in password)
$password = isset($_POST['password']) ? $_POST['password'] : '';

// ========== VALIDATION ==========
// Check if username and password are provided
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Username and password required']);
    exit();
}

// Check username length (3-50 characters)
if (strlen($username) < 3 || strlen($username) > 50) {
    echo json_encode(['success' => false, 'error' => 'Username must be 3-50 characters']);
    exit();
}

// Check username format (alphanumeric, underscore, hyphen only)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
    echo json_encode(['success' => false, 'error' => 'Username: only letters, numbers, _, - allowed']);
    exit();
}

// Check password length (minimum 6 characters)
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit();
}

// ========== RATE LIMITING ==========
// Check rate limit for this username (prevents brute force registration)
if (checkRateLimit('register_' . $username)) {
    // HTTP 429: Too Many Requests
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many registration attempts. Try again later.']);
    exit();
}

// ========== CHECK IF USERNAME EXISTS ==========
// Prepare query to check if username already exists
$stmt = $conn->prepare("SELECT id FROM test.users WHERE username = ?");
if (!$stmt) {
    // Database prepare error
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

// Bind username parameter (string type)
$stmt->bind_param("s", $username);
// Execute the query
$stmt->execute();

// Get result set
if ($stmt->get_result()->num_rows > 0) {
    // Username already exists in database
    echo json_encode(['success' => false, 'error' => 'Username already exists']);
    $stmt->close();
    exit();
}
// Close statement after use
$stmt->close();

// ========== HASH PASSWORD ==========
// Hash password using bcrypt with cost of 10 (optimized for 2GB RAM)
// PASSWORD_DEFAULT uses bcrypt
$password_hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => PASSWORD_HASH_COST]);

// ========== INSERT NEW USER ==========
// Prepare INSERT statement for new user
$stmt = $conn->prepare("INSERT INTO test.users (username, password_hash, role) VALUES (?, ?, ?)");
if (!$stmt) {
    // Database prepare error
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

// Default role is 'user' for new registrations
$role = 'user';
// Bind parameters: username (string), password_hash (string), role (string)
$stmt->bind_param("sss", $username, $password_hash, $role);

// Execute the INSERT statement
if ($stmt->execute()) {
    // Clear rate limit after successful registration
    clearRateLimit('register_' . $username);
    
    // Return success response with new user_id
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Please log in.',
        'user_id' => $stmt->insert_id
    ]);
} else {
    // INSERT failed
    echo json_encode(['success' => false, 'error' => 'Registration failed']);
}

// Close statement
$stmt->close();
// Close database connection
$conn->close();

?>
```

---

## 4. NEW FILE: restore_task.php - Restore Tasks from Archive

**Location**: `restore_task.php` (NEW FILE)  
**Purpose**: Move tasks from archive_tasks table back to tasks table

```php
<?php
/**
 * Restore Task Handler
 * Moves a task from archive_tasks back to tasks table
 * - Requires authentication
 * - Verifies user owns the archived task
 * - Syncs to archive_tasks.xml
 * - Returns JSON response
 */

// Include authentication check
include 'auth_check.php';
// Include XML sync helper
include 'xml_sync_helper.php';

// Set response content type to JSON
header('Content-Type: application/json');

// ========== AUTHENTICATION ==========
// Get authenticated user_id, exit if not logged in
$user_id = checkAuth();
if (!$user_id) {
    // HTTP 401: Unauthorized
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in']);
    exit();
}

// ========== METHOD VALIDATION ==========
// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // HTTP 405: Method Not Allowed
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

// ========== GET ARCHIVED TASK ID ==========
// Get archived task ID from POST parameter and convert to integer
$archive_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// ========== VALIDATE TASK ID ==========
// Check if archive_id is valid (greater than 0)
if ($archive_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
    exit();
}

// ========== GET ARCHIVED TASK DATA ==========
// Prepare query to get archived task (verify user owns it)
$stmt = $conn->prepare("SELECT id, title, description, status, created_at FROM test.archive_tasks WHERE id = ? AND user_id = ?");
if (!$stmt) {
    // Database prepare error
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

// Bind parameters: archive_id (int), user_id (int)
$stmt->bind_param("ii", $archive_id, $user_id);
// Execute query
$stmt->execute();
// Get result set
$result = $stmt->get_result();

// Check if archived task exists and belongs to user
if ($result->num_rows === 0) {
    // Task not found or doesn't belong to user
    echo json_encode(['success' => false, 'error' => 'Task not found']);
    $stmt->close();
    exit();
}

// Fetch the archived task data
$archived_task = $result->fetch_assoc();
$stmt->close();

// ========== MOVE TASK BACK TO ACTIVE TABLE ==========
// Prepare INSERT statement to move task to active tasks table
$insert_stmt = $conn->prepare("INSERT INTO test.tasks (user_id, title, description, status, created_at) VALUES (?, ?, ?, ?, ?)");
if (!$insert_stmt) {
    // Database prepare error
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

// Bind parameters for INSERT
$insert_stmt->bind_param(
    "issss",
    $user_id,                    // user_id (int)
    $archived_task['title'],     // title (string)
    $archived_task['description'],// description (string)
    $archived_task['status'],    // status (string)
    $archived_task['created_at'] // created_at (string)
);

// Execute INSERT
if (!$insert_stmt->execute()) {
    // INSERT failed
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to restore task']);
    $insert_stmt->close();
    exit();
}

// Get the new task ID (for XML sync)
$new_task_id = $insert_stmt->insert_id();
$insert_stmt->close();

// ========== DELETE FROM ARCHIVE TABLE ==========
// Prepare DELETE statement to remove from archive
$delete_stmt = $conn->prepare("DELETE FROM test.archive_tasks WHERE id = ? AND user_id = ?");
if (!$delete_stmt) {
    // Database prepare error (but task already restored, so just return success)
    http_response_code(500);
    echo json_encode(['success' => true, 'message' => 'Task restored but deletion failed']);
    exit();
}

// Bind parameters for DELETE
$delete_stmt->bind_param("ii", $archive_id, $user_id);
// Execute DELETE
$delete_stmt->execute();
$delete_stmt->close();

// ========== SYNC TO XML FILES ==========
// Add restored task to tasks.xml
syncTaskToXML([
    'id' => $new_task_id,
    'user_id' => $user_id,
    'title' => $archived_task['title'],
    'description' => $archived_task['description'],
    'status' => $archived_task['status'],
    'created_at' => $archived_task['created_at']
]);

// Remove archived task from archive_tasks.xml
removeTaskFromArchiveXML($archive_id, $user_id);

// ========== CLOSE CONNECTION AND RESPOND ==========
// Close database connection
$conn->close();

// Return success response with new task ID
echo json_encode([
    'success' => true,
    'message' => 'Task restored successfully',
    'new_task_id' => $new_task_id
]);

?>
```

---

## 5. NEW FILE: xml_sync_helper.php - XML Sync Functions

**Location**: `xml_sync_helper.php` (NEW FILE)  
**Purpose**: Handle XML backup/sync for tasks and archives

```php
<?php
/**
 * XML Sync Helper Functions
 * Keeps tasks.xml and archive_tasks.xml in sync with MySQL database
 * Ensures backup files always reflect database state
 */

// Include configuration
include 'config.php';

/**
 * Add or update a task in tasks.xml
 * If task exists, update it; if not, add it
 * Parameters: $task (array) - task data with id, user_id, title, description, status, created_at
 */
function syncTaskToXML($task) {
    // Check if tasks.xml exists
    if (!file_exists(TASKS_XML_FILE)) {
        // Create new XML file if it doesn't exist
        initializeTasksXML();
    }
    
    // Load the XML file into memory
    $xml = simplexml_load_file(TASKS_XML_FILE);
    
    // If XML is empty, reinitialize it
    if (!$xml) {
        initializeTasksXML();
        $xml = simplexml_load_file(TASKS_XML_FILE);
    }
    
    // Search for existing task by ID
    $existing_task = null;
    // XPath query to find task with matching ID
    $xpath_result = $xml->xpath("//task[@id='" . $task['id'] . "']");
    
    // Check if task was found
    if (count($xpath_result) > 0) {
        // Task exists, update it
        $existing_task = $xpath_result[0];
        // Update task data
        $existing_task->user_id = $task['user_id'];
        $existing_task->title = htmlspecialchars($task['title']);
        $existing_task->description = htmlspecialchars($task['description']);
        $existing_task->status = $task['status'];
        $existing_task->created_at = $task['created_at'];
    } else {
        // Task doesn't exist, add new one
        // Create new task element
        $new_task = $xml->addChild('task');
        // Set task ID as attribute
        $new_task->addAttribute('id', $task['id']);
        // Add task elements
        $new_task->addChild('user_id', $task['user_id']);
        $new_task->addChild('title', htmlspecialchars($task['title']));
        $new_task->addChild('description', htmlspecialchars($task['description']));
        $new_task->addChild('status', $task['status']);
        $new_task->addChild('created_at', $task['created_at']);
    }
    
    // Save XML file with proper formatting
    // DOMDocument is used for pretty printing
    $dom = new DOMDocument('1.0', 'UTF-8');
    // Load XML with preserveWhitespace=false for formatting
    $dom->preserveWhiteSpace = false;
    // Load from string
    $dom->loadXML($xml->asXML());
    // Format output nicely
    $dom->formatOutput = true;
    
    // Write formatted XML to file
    // FILE_TEXT_MODE preserves line endings
    file_put_contents(TASKS_XML_FILE, $dom->saveXML(), FILE_TEXT_MODE);
    
    // Validate XML against XSD schema
    validateTasksXML();
}

/**
 * Remove a task from tasks.xml
 * Parameters: $task_id (int) - ID of task to remove
 */
function removeTaskFromXML($task_id) {
    // Check if tasks.xml exists
    if (!file_exists(TASKS_XML_FILE)) {
        // Nothing to remove if file doesn't exist
        return;
    }
    
    // Load XML file
    $xml = simplexml_load_file(TASKS_XML_FILE);
    
    // If XML load failed, exit
    if (!$xml) {
        return;
    }
    
    // Find all tasks with matching ID
    foreach ($xml->xpath("//task[@id='" . $task_id . "']") as $task) {
        // Convert SimpleXMLElement to DOMElement for deletion
        $dom = dom_import_simplexml($task);
        // Remove the node from DOM tree
        $dom->parentNode->removeChild($dom);
    }
    
    // Create DOMDocument for pretty printing
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    // Load formatted XML
    $dom->loadXML($xml->asXML());
    $dom->formatOutput = true;
    
    // Write updated XML to file
    file_put_contents(TASKS_XML_FILE, $dom->saveXML(), FILE_TEXT_MODE);
    
    // Validate against schema
    validateTasksXML();
}

/**
 * Add or update a task in archive_tasks.xml
 * Parameters: $task (array) - archived task data
 */
function syncTaskToArchiveXML($task) {
    // Check if archive_tasks.xml exists
    if (!file_exists(ARCHIVE_XML_FILE)) {
        // Create new archive XML file if missing
        initializeArchiveXML();
    }
    
    // Load archive XML
    $xml = simplexml_load_file(ARCHIVE_XML_FILE);
    
    // If load failed, reinitialize
    if (!$xml) {
        initializeArchiveXML();
        $xml = simplexml_load_file(ARCHIVE_XML_FILE);
    }
    
    // Search for existing archived task by ID
    $xpath_result = $xml->xpath("//archived_task[@id='" . $task['id'] . "']");
    
    // Check if archived task exists
    if (count($xpath_result) > 0) {
        // Update existing
        $existing = $xpath_result[0];
        $existing->user_id = $task['user_id'];
        $existing->title = htmlspecialchars($task['title']);
        $existing->description = htmlspecialchars($task['description']);
        $existing->status = $task['status'];
        $existing->created_at = $task['created_at'];
        $existing->archived_at = $task['archived_at'];
    } else {
        // Add new archived task
        $new_task = $xml->addChild('archived_task');
        // Set ID as attribute
        $new_task->addAttribute('id', $task['id']);
        // Add all data
        $new_task->addChild('user_id', $task['user_id']);
        $new_task->addChild('title', htmlspecialchars($task['title']));
        $new_task->addChild('description', htmlspecialchars($task['description']));
        $new_task->addChild('status', $task['status']);
        $new_task->addChild('created_at', $task['created_at']);
        $new_task->addChild('archived_at', $task['archived_at']);
    }
    
    // Format and save with DOMDocument
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $dom->formatOutput = true;
    
    // Write to file
    file_put_contents(ARCHIVE_XML_FILE, $dom->saveXML(), FILE_TEXT_MODE);
    
    // Validate against schema
    validateArchiveXML();
}

/**
 * Remove task from archive_tasks.xml
 * Parameters: $task_id (int), $user_id (int)
 */
function removeTaskFromArchiveXML($task_id, $user_id) {
    // Check if archive XML exists
    if (!file_exists(ARCHIVE_XML_FILE)) {
        return;
    }
    
    // Load archive XML
    $xml = simplexml_load_file(ARCHIVE_XML_FILE);
    
    // If load failed, exit
    if (!$xml) {
        return;
    }
    
    // Find all archived tasks with matching ID
    foreach ($xml->xpath("//archived_task[@id='" . $task_id . "']") as $task) {
        // Convert to DOM for deletion
        $dom = dom_import_simplexml($task);
        // Remove from tree
        $dom->parentNode->removeChild($dom);
    }
    
    // Format and save
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $dom->formatOutput = true;
    
    file_put_contents(ARCHIVE_XML_FILE, $dom->saveXML(), FILE_TEXT_MODE);
    
    // Validate
    validateArchiveXML();
}

/**
 * Initialize tasks.xml with proper structure
 */
function initializeTasksXML() {
    // Create XML declaration and root element
    $xml_content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tasks xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="tasks.xsd">
</tasks>
XML;
    
    // Write initial XML to file
    file_put_contents(TASKS_XML_FILE, $xml_content, FILE_TEXT_MODE);
}

/**
 * Initialize archive_tasks.xml with proper structure
 */
function initializeArchiveXML() {
    // Create XML declaration and root element for archives
    $xml_content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<archived_tasks xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="archive_tasks.xsd">
</archived_tasks>
XML;
    
    // Write initial XML to file
    file_put_contents(ARCHIVE_XML_FILE, $xml_content, FILE_TEXT_MODE);
}

/**
 * Validate tasks.xml against tasks.xsd schema
 * Returns true if valid, false otherwise
 */
function validateTasksXML() {
    // Check if XML file exists
    if (!file_exists(TASKS_XML_FILE)) {
        return true; // No file to validate
    }
    
    // Check if XSD schema exists
    if (!file_exists(TASKS_XSD_FILE)) {
        return true; // No schema to validate against
    }
    
    // Create DOM document
    $dom = new DOMDocument();
    // Load the XML file
    $dom->load(TASKS_XML_FILE);
    
    // Validate against XSD schema
    // Returns true if valid, false if invalid
    return @$dom->schemaValidate(TASKS_XSD_FILE);
}

/**
 * Validate archive_tasks.xml against archive_tasks.xsd
 */
function validateArchiveXML() {
    // Check if archive XML exists
    if (!file_exists(ARCHIVE_XML_FILE)) {
        return true;
    }
    
    // Check if schema exists
    if (!file_exists(ARCHIVE_XSD_FILE)) {
        return true;
    }
    
    // Create and validate
    $dom = new DOMDocument();
    $dom->load(ARCHIVE_XML_FILE);
    
    // Return validation result
    return @$dom->schemaValidate(ARCHIVE_XSD_FILE);
}

?>
```

---

## 6. UPDATE add_task.php - Add XML Sync

**Location**: `add_task.php`  
**Purpose**: Sync new tasks to tasks.xml

Replace with:

```php
<?php
/**
 * Add Task Handler
 * Creates a new task in database and XML backup
 * - Requires authentication
 * - Validates input
 * - Syncs to tasks.xml
 * - Returns new task ID
 */

// Include authentication check
include 'auth_check.php';
// Include XML sync helper
include 'xml_sync_helper.php';

// Set response content type to JSON
header('Content-Type: application/json');

// ========== AUTHENTICATION ==========
// Get authenticated user_id
$user_id = checkAuth();
// Check if user is logged in
if (!$user_id) {
    // HTTP 401: Unauthorized
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in']);
    exit();
}

// ========== METHOD VALIDATION ==========
// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // HTTP 405: Method Not Allowed
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

// ========== GET INPUT DATA ==========
// Get task title from POST and trim whitespace
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
// Get task description from POST and trim whitespace
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// ========== VALIDATE INPUT ==========
// Check if title is empty
if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Title required']);
    exit();
}

// ========== INSERT INTO DATABASE ==========
// Prepare INSERT statement for new task
$stmt = $conn->prepare("INSERT INTO test.tasks (user_id, title, description, status) VALUES (?, ?, ?, 'pending')");
// Check if prepare was successful
if (!$stmt) {
    // Database error
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}

// Bind parameters: user_id (int), title (string), description (string)
$stmt->bind_param("iss", $user_id, $title, $description);

// ========== EXECUTE AND SYNC ==========
// Execute the INSERT statement
if ($stmt->execute()) {
    // Get the new task ID generated by AUTO_INCREMENT
    $new_task_id = $stmt->insert_id;
    
    // ========== SYNC TO XML ==========
    // Create task array for XML sync
    $task_data = [
        'id' => $new_task_id,
        'user_id' => $user_id,
        'title' => $title,
        'description' => $description,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Add task to tasks.xml backup
    syncTaskToXML($task_data);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'task_id' => $new_task_id,
        'message' => 'Task created successfully'
    ]);
} else {
    // Execute failed
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to add task']);
}

// Close prepared statement
$stmt->close();
// Close database connection
$conn->close();

?>
```

---

## 7. UPDATE edit_task.php - Add XML Sync

**Location**: `edit_task.php`  
**Purpose**: Sync task updates to tasks.xml

Replace with:

```php
<?php
/**
 * Edit Task Handler
 * Updates an existing task in database and XML
 * - Requires authentication
 * - Verifies user owns the task
 * - Validates input
 * - Syncs to tasks.xml
 * - Returns success/error
 */

// Include authentication
include 'auth_check.php';
// Include XML sync helper
include 'xml_sync_helper.php';

// ========== AUTHENTICATION ==========
// Get authenticated user_id
$user_id = checkAuth();
// Check if user is logged in
if (!$user_id) {
    // Not authenticated, redirect to login
    header('Location: login.html');
    exit();
}

// ========== METHOD VALIDATION ==========
// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Not a POST request, redirect
    header("Location: tasks.php");
    exit();
}

// ========== GET INPUT DATA ==========
// Get task ID from POST and convert to integer
$task_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
// Get updated title from POST
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
// Get updated description from POST
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// ========== DETECT AJAX REQUEST ==========
// Check if this is an AJAX request (has X-Requested-With header)
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// If AJAX, set JSON response header
if ($isAjax) {
    header('Content-Type: application/json');
}

// ========== VALIDATE TASK ID ==========
// Check if task_id is valid (greater than 0)
if ($task_id <= 0) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
    } else {
        header("Location: tasks.php?error=Invalid task ID");
    }
    exit();
}

// ========== VALIDATE TITLE ==========
// Check if title is empty
if (empty($title)) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'Task title is required']);
    } else {
        header("Location: tasks.php?error=Task title is required");
    }
    exit();
}

// ========== GET ORIGINAL TASK (FOR XML SYNC) ==========
// Query to get original task data before update
$get_stmt = $conn->prepare("SELECT status, created_at FROM test.tasks WHERE id = ? AND user_id = ?");
if ($get_stmt) {
    // Bind parameters
    $get_stmt->bind_param("ii", $task_id, $user_id);
    // Execute query
    $get_stmt->execute();
    // Get result
    $get_result = $get_stmt->get_result();
    
    // Check if task exists
    if ($get_result->num_rows > 0) {
        // Fetch original task data
        $original_task = $get_result->fetch_assoc();
    } else {
        // Task not found
        if ($isAjax) {
            echo json_encode(['success' => false, 'error' => 'Task not found']);
        } else {
            header("Location: tasks.php?error=Task not found");
        }
        $get_stmt->close();
        exit();
    }
    
    $get_stmt->close();
}

// ========== UPDATE TASK IN DATABASE ==========
// Prepare UPDATE statement
// WHERE clause includes user_id check to prevent users from updating others' tasks
$stmt = $conn->prepare("UPDATE test.tasks SET title = ?, description = ? WHERE id = ? AND user_id = ?");
// Check if prepare was successful
if (!$stmt) {
    // Database error
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    } else {
        header("Location: tasks.php?error=Database error");
    }
    exit();
}

// Bind parameters: title (string), description (string), task_id (int), user_id (int)
$stmt->bind_param("ssii", $title, $description, $task_id, $user_id);

// ========== EXECUTE AND SYNC ==========
// Execute the UPDATE
if ($stmt->execute()) {
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        // ========== SYNC TO XML ==========
        // Create updated task array
        $task_data = [
            'id' => $task_id,
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            'status' => $original_task['status'],  // Keep original status
            'created_at' => $original_task['created_at']  // Keep original created_at
        ];
        
        // Update task in tasks.xml
        syncTaskToXML($task_data);
        
        // Task updated successfully
        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => 'Task updated']);
        } else {
            header("Location: tasks.php?success=Task updated");
        }
    } else {
        // No rows updated (task not found or user doesn't own it)
        if ($isAjax) {
            echo json_encode(['success' => false, 'error' => 'Task not found or permission denied']);
        } else {
            header("Location: tasks.php?error=Task not found or permission denied");
        }
    }
} else {
    // Execute failed
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update task']);
    } else {
        header("Location: tasks.php?error=Failed to update task");
    }
}

// Close prepared statement
$stmt->close();
// Close database connection
$conn->close();

?>
```

---

## 8. UPDATE delete_task.php - Move to Archive Instead of Permanent Delete

**Location**: `delete_task.php`  
**Purpose**: Move tasks to archive instead of permanently deleting

Replace with:

```php
<?php
/**
 * Delete Task Handler (Archive)
 * Moves a task to archive_tasks instead of permanently deleting
 * - Requires authentication
 * - Verifies user owns the task
 * - Handles both AJAX and form submissions
 * - Syncs to archive_tasks.xml
 * - Returns JSON or redirect
 */

// Include authentication
include 'auth_check.php';
// Include XML sync helper
include 'xml_sync_helper.php';

// ========== AUTHENTICATION ==========
// Get authenticated user_id
$user_id = checkAuth();
// Check if user is logged in
if (!$user_id) {
    // Not authenticated, redirect to login
    header('Location: login.html');
    exit();
}

// ========== METHOD VALIDATION ==========
// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Not a POST request, redirect
    header('Location: tasks.php');
    exit();
}

// ========== GET INPUT DATA ==========
// Get task ID from POST and convert to integer
$task_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// ========== DETECT AJAX REQUEST ==========
// Check if this is an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// If AJAX, set JSON response header
if ($isAjax) {
    header('Content-Type: application/json');
}

// ========== VALIDATE TASK ID ==========
// Check if task_id is valid (greater than 0)
if ($task_id <= 0) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
    } else {
        header("Location: tasks.php?error=Invalid task ID");
    }
    exit();
}

// ========== GET TASK DATA (FOR ARCHIVING) ==========
// Query to get task details before moving to archive
$get_stmt = $conn->prepare("SELECT id, user_id, title, description, status, created_at FROM test.tasks WHERE id = ? AND user_id = ?");
if (!$get_stmt) {
    // Database error
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    } else {
        header("Location: tasks.php?error=Database error");
    }
    exit();
}

// Bind parameters: task_id (int), user_id (int)
$get_stmt->bind_param("ii", $task_id, $user_id);
// Execute query
$get_stmt->execute();
// Get result
$get_result = $get_stmt->get_result();

// Check if task exists and belongs to user
if ($get_result->num_rows === 0) {
    // Task not found or user doesn't own it
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'Task not found or permission denied']);
    } else {
        header("Location: tasks.php?error=Task not found or permission denied");
    }
    $get_stmt->close();
    exit();
}

// Fetch task data to preserve it in archive
$task = $get_result->fetch_assoc();
$get_stmt->close();

// ========== MOVE TASK TO ARCHIVE TABLE ==========
// Prepare INSERT statement to move to archive_tasks table
$archive_stmt = $conn->prepare("INSERT INTO test.archive_tasks (user_id, title, description, status, created_at, archived_at) VALUES (?, ?, ?, ?, ?, NOW())");
if (!$archive_stmt) {
    // Database error
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    } else {
        header("Location: tasks.php?error=Database error");
    }
    exit();
}

// Bind parameters for archive insert
$archive_stmt->bind_param(
    "issss",
    $task['user_id'],        // user_id (int)
    $task['title'],          // title (string)
    $task['description'],    // description (string)
    $task['status'],         // status (string)
    $task['created_at']      // created_at (string)
);

// Execute INSERT to archive
if (!$archive_stmt->execute()) {
    // INSERT to archive failed
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to archive task']);
    } else {
        header("Location: tasks.php?error=Failed to archive task");
    }
    $archive_stmt->close();
    exit();
}

// Get the new archive ID
$archive_id = $archive_stmt->insert_id;
$archive_stmt->close();

// ========== DELETE FROM ACTIVE TASKS TABLE ==========
// Prepare DELETE statement to remove from active tasks
$delete_stmt = $conn->prepare("DELETE FROM test.tasks WHERE id = ? AND user_id = ?");
if (!$delete_stmt) {
    // Database error (but task already archived, so return partial success)
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => true, 'message' => 'Task archived but deletion failed']);
    } else {
        header("Location: tasks.php?success=Task archived");
    }
    exit();
}

// Bind parameters for DELETE
$delete_stmt->bind_param("ii", $task_id, $user_id);
// Execute DELETE
$delete_stmt->execute();
// Get number of affected rows
$deleted_rows = $delete_stmt->affected_rows;
$delete_stmt->close();

// ========== SYNC TO XML FILES ==========
// Remove task from tasks.xml (it's now archived)
removeTaskFromXML($task_id);

// Add archived task to archive_tasks.xml
$archived_task_data = [
    'id' => $archive_id,
    'user_id' => $task['user_id'],
    'title' => $task['title'],
    'description' => $task['description'],
    'status' => $task['status'],
    'created_at' => $task['created_at'],
    'archived_at' => date('Y-m-d H:i:s')
];
// Sync to archive XML
syncTaskToArchiveXML($archived_task_data);

// ========== CLOSE CONNECTION AND RESPOND ==========
// Close database connection
$conn->close();

// Check if deletion was successful
if ($deleted_rows > 0) {
    // Task successfully archived
    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => 'Task archived successfully']);
    } else {
        header("Location: tasks.php?success=Task archived successfully");
    }
} else {
    // Deletion failed but archive succeeded
    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => 'Task archived but failed to remove from active']);
    } else {
        header("Location: tasks.php?error=Task archived but failed to remove from active");
    }
}

?>
```

---

## 9. UPDATE database_setup.php - Add Archive Tables & Roles

See next message (character limit). This adds:
- `archive_tasks` table for deleted tasks
- `role` column to `users` table (admin/user)
- Proper indexing for performance

---

## 10. UPDATE script.js - Fix Refresh Issues & Add Archive UI

See next message (comprehensive frontend fixes with inline comments).

---

## 11. NEW: tasks.xsd & archive_tasks.xsd

See next message (XML schema validation files).

---

## TESTING CHECKLIST

- [ ] Test task creation → should sync to XML
- [ ] Test task editing → should sync to XML
- [ ] Test task deletion → should move to archive, sync XMLs
- [ ] Test task restore → should move back from archive
- [ ] Test registration with DEV_MODE=true → no rate limiting
- [ ] Test admin dashboard (if available) → shows all tasks
- [ ] Test non-admin dashboard → shows only own tasks
- [ ] Verify no manual refresh needed after any operation
- [ ] Check `data/tasks.xml` and `data/archive_tasks.xml` exist and update

**Next responses will include remaining code files**


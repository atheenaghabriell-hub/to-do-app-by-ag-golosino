<?php
/**
 * Configuration file for the To-Do App
 * Store all constants and configuration settings here
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password - change if needed
define('DB_NAME', 'test');

// Session configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('TOKEN_LENGTH', 32); // Bytes for random_bytes()

// Security settings
define('MIN_PASSWORD_LENGTH', 6);
define('SESSION_NAME', 'todo_app');

// Paths
define('BASE_URL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/to-do-app-by-ag-golosino/');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
?>
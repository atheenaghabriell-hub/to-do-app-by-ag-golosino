<?php
/**
 * Configuration file for the To-Do App
 * Optimized for 2GB RAM systems
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
define('PASSWORD_HASH_COST', 10); // Reduced from 12 for lower memory usage

// Paths
define('BASE_URL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/to-do-app-by-ag-golosino/');

// Performance settings for 2GB RAM
define('QUERY_CACHE_SIZE', 16777216); // 16MB
define('MAX_CONNECTIONS', 10);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('memory_limit', '64M'); // Reduced memory limit
ini_set('max_execution_time', 30);

// Enable gzip compression
if (!headers_sent()) {
    ob_start('ob_gzhandler');
}
?>

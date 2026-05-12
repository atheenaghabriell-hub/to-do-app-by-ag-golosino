<?php
/**
 * Configuration file for the To-Do App
 * Optimized for low-resource systems (2GB RAM)
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'test');

// Memory optimization
ini_set('memory_limit', '64M'); // Reduced from default for 2GB RAM systems
ini_set('max_execution_time', 30);
ini_set('default_socket_timeout', 10);

// Session configuration
define('SESSION_TIMEOUT', 3600);
define('TOKEN_LENGTH', 32);
define('SESSION_NAME', 'todo_app');

// Security settings
define('MIN_PASSWORD_LENGTH', 6);
define('PASSWORD_HASH_COST', 10); // Optimized for performance

// Cache settings (reduces server load)
define('TASK_CACHE_DURATION', 30); // Cache tasks for 30 seconds
define('MAX_TASKS_PER_REQUEST', 500); // Limit query results

// Paths
define('BASE_URL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/to-do-app-by-ag-golosino/');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Output compression (reduces bandwidth)
if (!ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 1);
    ini_set('zlib.output_compression_level', 6);
}
?>
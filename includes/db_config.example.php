<?php
/**
 * Database Configuration
 * Centralized database connection credentials
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');

/**
 * Create database connection
 * @return mysqli Database connection object
 */
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Create global connection
$conn = getDbConnection();
?>

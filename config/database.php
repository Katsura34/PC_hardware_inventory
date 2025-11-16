<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pc_inventory');

// Create connection
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
                die("Connection failed. Please try again later.");
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            die("Database error. Please try again later.");
        }
    }
    
    return $conn;
}

// Close connection
function closeDBConnection() {
    $conn = getDBConnection();
    if ($conn) {
        $conn->close();
    }
}
?>

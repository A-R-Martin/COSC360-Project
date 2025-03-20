<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "virtual_library";

// Create error log file
$db_log_file = 'db_connect_log.txt';
function db_log($message) {
    global $db_log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($db_log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

db_log("Attempting to connect to database: Host=$host, User=$username, DB=$database");

try {
    // Create connection
    $conn = mysqli_connect($host, $username, $password, $database);

    // Check connection
    if (!$conn) {
        db_log("First connection attempt failed: " . mysqli_connect_error());
        
        // Try 127.0.0.1 if fail
        $host = "127.0.0.1";
        db_log("Trying alternative host: $host");
        $conn = mysqli_connect($host, $username, $password, $database);
        
        if (!$conn) {
            db_log("Second connection attempt failed: " . mysqli_connect_error());
            die("Connection failed: " . mysqli_connect_error());
        }
    }

    db_log("Successfully connected to database");

    // Set charset to ensure proper encoding
    mysqli_set_charset($conn, "utf8mb4");
    db_log("Charset set to utf8mb4");

    // Check if the users table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if (mysqli_num_rows($result) == 0) {
        db_log("WARNING: 'users' table does not exist in the database");
    } else {
        db_log("'users' table exists in the database");
    }
} catch (Exception $e) {
    db_log("Exception caught: " . $e->getMessage());
    die("Connection error: " . $e->getMessage());
}

// error reporting for development
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    // Local - show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    db_log("Error reporting enabled for local environment");
} else {
    // Prod- hide errors
    error_reporting(0);
    ini_set('display_errors', 0);
    db_log("Error reporting disabled for production environment");
}

// sanitize input
function sanitize($conn, $input) {
    if (is_array($input)) {
        $sanitized = array();
        foreach ($input as $key => $value) {
            $sanitized[$key] = sanitize($conn, $value);
        }
        return $sanitized;
    }
    return mysqli_real_escape_string($conn, trim($input));
}

function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
?> 
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
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $conn = new PDO($dsn, $username, $password, $options);
    db_log("Successfully connected to database using PDO");
    
    // Check if the users table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        db_log("WARNING: 'users' table does not exist in the database");
    } else {
        db_log("'users' table exists in the database");
    }
    
} catch (PDOException $e) {
    db_log("Connection failed: " . $e->getMessage());
    
    // Try alternative host
    try {
        $host = "127.0.0.1";
        db_log("Trying alternative host: $host");
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $conn = new PDO($dsn, $username, $password, $options);
        db_log("Successfully connected to database using alternative host");
    } catch (PDOException $e2) {
        db_log("Second connection attempt failed: " . $e2->getMessage());
        die("Connection failed: " . $e2->getMessage());
    }
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

// sanitize input function
function sanitize($input) {
    if (is_array($input)) {
        $sanitized = array();
        foreach ($input as $key => $value) {
            $sanitized[$key] = sanitize($value);
        }
        return $sanitized;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
?> 
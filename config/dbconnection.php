<?php
// Detect environment
$isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1');

if ($isLocal) {
    // XAMPP Local Settings
    define('DB_SERVER', '127.0.0.1');
    define('DB_USERNAME', 'root');
    define('DB_PASSWORD', '');
    define('DB_NAME', 'business-directory');
} else {
    // InfinityFree Live Settings - read from .env
    $env = parse_ini_file(__DIR__ . '/.env');
    define('DB_SERVER', $env['DB_SERVER']);
    define('DB_USERNAME', $env['DB_USERNAME']);
    define('DB_PASSWORD', $env['DB_PASSWORD']);
    define('DB_NAME', $env['DB_NAME']);
}

function getDBConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Nairobi');
?>
<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session with security settings
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 1800, // 30 minutes
        'cookie_secure' => false, // Set to true if using HTTPS
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Azure SQL Database configuration
$server = 'tbserver2025.database.windows.net,1433';
$database = 'if0_40840685_tech_blog';
$username = 'CloudSA219c14b7';
$password = 'Tanaka117';

try {
    // Create PDO connection
    $conn = new PDO("sqlsrv:server = tcp:$server; Database = $database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Site configuration
define('SITE_NAME', 'Tech Blog Pro');
define('SITE_URL', 'http://localhost/tech-blog');
define('ADMIN_EMAIL', 'admin@techblog.com');

// File upload configuration
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_PATH', 'uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Set timezone
date_default_timezone_set('UTC');
?>

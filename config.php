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

// Database configuration (still needed for blog posts)
$host = 'sql105.infinityfree.com';
$user = 'if0_40840685';
$password = 'Munjanja2026';
$database = 'if0_40840685_tech_blog';

// Create connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, 'utf8mb4');

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
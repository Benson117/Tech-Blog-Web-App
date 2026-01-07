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

// Site configuration
define('SITE_NAME', 'Tech Blog Pro');
define('SITE_URL', 'http://localhost/tech-blog');
define('ADMIN_EMAIL', 'admin@techblog.com');
define('MAX_FILE_SIZE', 2097152); // 2MB in bytes
define('ALLOWED_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_PATH', 'uploads/');

// Set timezone
date_default_timezone_set('UTC');

// Azure SQL Database Connection using PDO
try {
    $serverName = "tcp:tbserver2025.database.windows.net,1433";
    $database = "if0_40840685_tech_blog";
    $username = "CloudSA219c14b7";
    $password = "Tanaka117";
    
    // Using PDO for SQL Server
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
    
    // Test connection
    $conn->query("SELECT 1");
    
} catch (PDOException $e) {
    die("Error connecting to SQL Server: " . $e->getMessage());
}

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Helper function to execute queries with error handling
function executeQuery($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " SQL: " . $sql);
        throw $e;
    }
}

// Helper function to fetch single row
function fetchSingle($conn, $sql, $params = []) {
    $stmt = executeQuery($conn, $sql, $params);
    return $stmt->fetch();
}

// Helper function to fetch all rows
function fetchAll($conn, $sql, $params = []) {
    $stmt = executeQuery($conn, $sql, $params);
    return $stmt->fetchAll();
}

// Helper function to get last insert ID
function lastInsertId($conn) {
    return $conn->lastInsertId();
}

// Sanitize input function
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Require admin authentication
function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('admin-login.php');
    }
}

// File upload function
function uploadFile($file, $allowedTypes = ALLOWED_TYPES, $maxSize = MAX_FILE_SIZE) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds limit'];
    }
    
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = UPLOAD_PATH . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => $targetPath, 'filename' => $fileName];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

// Generate excerpt from content
function generateExcerpt($content, $length = 150) {
    $content = strip_tags($content);
    if (strlen($content) > $length) {
        $content = substr($content, 0, $length) . '...';
    }
    return $content;
}

// Format date
function formatDate($dateString, $format = 'F j, Y') {
    $date = new DateTime($dateString);
    return $date->format($format);
}

// Get comment count for post
function getCommentCount($conn, $postId) {
    $sql = "SELECT COUNT(*) as count FROM comments WHERE post_id = ? AND status = 'approved'";
    $result = fetchSingle($conn, $sql, [$postId]);
    return $result ? $result['count'] : 0;
}

// Get popular posts
function getPopularPosts($conn, $limit = 5) {
    $sql = "SELECT * FROM blog_posts WHERE status = 'published' ORDER BY views DESC LIMIT ?";
    return fetchAll($conn, $sql, [$limit]);
}

// Get recent posts
function getRecentPosts($conn, $limit = 6) {
    $sql = "SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT ?";
    return fetchAll($conn, $sql, [$limit]);
}

// Get featured post
function getFeaturedPost($conn) {
    $sql = "SELECT TOP 1 * FROM blog_posts WHERE status = 'published' AND image_path IS NOT NULL ORDER BY created_at DESC";
    return fetchSingle($conn, $sql);
}

// Get categories (authors)
function getCategories($conn, $limit = 6) {
    $sql = "SELECT DISTINCT author, COUNT(*) as post_count 
            FROM blog_posts 
            WHERE status = 'published' 
            GROUP BY author 
            ORDER BY post_count DESC 
            LIMIT ?";
    return fetchAll($conn, $sql, [$limit]);
}

// Get blog statistics
function getBlogStats($conn) {
    $stats = [];
    
    // Total posts
    $sql = "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'";
    $result = fetchSingle($conn, $sql);
    $stats['total_posts'] = $result['total'] ?? 0;
    
    // Total views
    $sql = "SELECT SUM(views) as total FROM blog_posts WHERE status = 'published'";
    $result = fetchSingle($conn, $sql);
    $stats['total_views'] = $result['total'] ?? 0;
    
    // Total comments
    $sql = "SELECT COUNT(*) as total FROM comments WHERE status = 'approved'";
    $result = fetchSingle($conn, $sql);
    $stats['total_comments'] = $result['total'] ?? 0;
    
    return $stats;
}

// Close connection function (optional, as PDO closes automatically)
function closeConnection($conn = null) {
    $conn = null;
}

// Auto-close connection at script end
register_shutdown_function(function() use ($conn) {
    closeConnection($conn);
});
?>

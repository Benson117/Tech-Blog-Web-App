<?php
// ============================================
// CONFIGURATION & DATABASE CONNECTION
// ============================================

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'Tech Blog Pro');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('ADMIN_EMAIL', 'admin@techblog.com');
define('MAX_FILE_SIZE', 2097152); // 2MB
define('UPLOAD_PATH', 'uploads/');

// Set timezone
date_default_timezone_set('UTC');

// Database connection variables
$host = 'tcp:tbserver2025.database.windows.net,1433';
$dbname = 'if0_40840685_tech_blog';
$username = 'CloudSA219c14b7';
$password = 'Tanaka117';
$conn = null;

// Try to create a database connection
try {
    // Try PDO MySQL first (most common)
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // If connection fails, create demo mode
    $conn = null;
    $demo_mode = true;
    error_log("Database connection failed: " . $e->getMessage());
}

// Helper functions
function executeQuery($conn, $sql, $params = []) {
    if (!$conn) return false;
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

function fetchSingle($conn, $sql, $params = []) {
    if (!$conn) {
        // Demo data for testing
        return [
            'id' => 1,
            'title' => 'Welcome to Tech Blog Pro',
            'author' => 'Admin',
            'views' => 150,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    $stmt = executeQuery($conn, $sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

function fetchAll($conn, $sql, $params = []) {
    if (!$conn) {
        // Demo data for testing
        return [
            [
                'id' => 1,
                'title' => 'Getting Started with PHP 8',
                'excerpt' => 'Learn the basics of PHP 8 and its new features',
                'author' => 'John Doe',
                'views' => 150,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'image_path' => null
            ],
            [
                'id' => 2,
                'title' => 'React vs Vue: Which to Choose in 2024',
                'excerpt' => 'Comparison of two popular JavaScript frameworks',
                'author' => 'Jane Smith',
                'views' => 210,
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'image_path' => null
            ]
        ];
    }
    
    $stmt = executeQuery($conn, $sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getCommentCount($conn, $postId) {
    if (!$conn) return 5; // Demo count
    
    $sql = "SELECT COUNT(*) as count FROM comments WHERE post_id = ? AND status = 'approved'";
    $result = fetchSingle($conn, $sql, [$postId]);
    return $result ? $result['count'] : 0;
}

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH) && is_writable(dirname(UPLOAD_PATH))) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// ============================================
// PAGE DATA FETCHING
// ============================================

// Get pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$posts_per_page = 6;
$offset = ($page - 1) * $posts_per_page;

// Get total posts count
$total_sql = "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'";
$total_result = fetchSingle($conn, $total_sql);
$total_posts = $total_result ? $total_result['total'] : 2; // Demo count
$total_pages = ceil($total_posts / $posts_per_page);
if ($total_pages < 1) $total_pages = 1;

// Get posts for current page
$posts_sql = "SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT ? OFFSET ?";
$posts = fetchAll($conn, $posts_sql, [$posts_per_page, $offset]);

// Get featured post
$featured_sql = "SELECT * FROM blog_posts WHERE status = 'published' AND image_path IS NOT NULL ORDER BY created_at DESC LIMIT 1";
$featured_post = fetchSingle($conn, $featured_sql);

// Get popular posts
$popular_sql = "SELECT * FROM blog_posts WHERE status = 'published' ORDER BY views DESC LIMIT 5";
$popular_posts = fetchAll($conn, $popular_sql);

// Get categories
$categories_sql = "SELECT DISTINCT author, COUNT(*) as post_count FROM blog_posts WHERE status = 'published' GROUP BY author ORDER BY post_count DESC LIMIT 6";
$categories = fetchAll($conn, $categories_sql);

// Get total stats
$stats_sql = "SELECT 
        COUNT(*) as total_posts,
        COALESCE(SUM(views), 0) as total_views,
        (SELECT COUNT(*) FROM comments WHERE status = 'approved') as total_comments
        FROM blog_posts WHERE status = 'published'";
$stats = fetchSingle($conn, $stats_sql);
if (!$stats) {
    $stats = ['total_posts' => 2, 'total_views' => 360, 'total_comments' => 8];
}

// ============================================
// HTML OUTPUT
// ============================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Technology Insights & Programming Tutorials</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        /* CSS Styles (same as before, but shortened for brevity) */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #8b5cf6;
            --accent: #10b981;
            --dark: #1f2937;
            --darker: #111827;
            --light: #f9fafb;
            --lighter: #ffffff;
            --gray: #6b7280;
            --gray-light: #9ca3af;
            --border: #e5e7eb;
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-dark: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.15);
            --shadow-xl: 0 15px 50px rgba(0,0,0,0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            line-height: 1.7;
            background: var(--light);
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        /* Header */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-sm);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        header.scrolled {
            padding: 0.5rem 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-md);
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 0;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: translateY(-2px);
        }
        
        .logo-icon {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.2rem;
        }
        
        .nav-links {
            display: flex;
            gap: 2.5rem;
            align-items: center;
        }
        
        .nav-link {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            position: relative;
            padding: 0.5rem 0;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--primary);
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .search-btn {
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .search-btn:hover {
            color: var(--primary);
        }
        
        /* Hero Section */
        .hero {
            background: var(--gradient-dark);
            color: white;
            padding: 8rem 0 6rem;
            margin-top: 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><path d="M0,0V100H1000V0C800,50 500,100 0,0Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: 100% 100px;
            background-position: bottom;
            opacity: 0.1;
        }
        
        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            background: linear-gradient(to right, #ffffff, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 3rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            color: white;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Featured Post */
        .featured-section {
            padding: 4rem 0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }
        
        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark);
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }
        
        .section-subtitle {
            color: var(--gray);
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }
        
        .featured-post {
            background: var(--lighter);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .featured-post:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
        }
        
        .featured-image {
            width: 100%;
            height: 100%;
            min-height: 400px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .featured-post:hover .featured-image {
            transform: scale(1.05);
        }
        
        .featured-content {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .featured-badge {
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1.5rem;
            align-self: flex-start;
        }
        
        .featured-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.3;
        }
        
        .featured-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .featured-title a:hover {
            color: var(--primary);
        }
        
        .featured-excerpt {
            color: var(--gray);
            margin-bottom: 2rem;
            font-size: 1.1rem;
            line-height: 1.8;
        }
        
        /* Posts Grid */
        .posts-section {
            padding: 4rem 0;
            background: var(--light);
        }
        
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2.5rem;
            margin-bottom: 4rem;
        }
        
        .post-card {
            background: var(--lighter);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .post-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }
        
        .post-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .post-card:hover::before {
            opacity: 1;
        }
        
        .post-image-container {
            position: relative;
            overflow: hidden;
            height: 220px;
        }
        
        .post-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .post-card:hover .post-image {
            transform: scale(1.1);
        }
        
        .post-category {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: var(--lighter);
            color: var(--primary);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }
        
        .post-content {
            padding: 2rem;
        }
        
        .post-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        
        .post-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .post-title a:hover {
            color: var(--primary);
        }
        
        .post-excerpt {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.7;
            font-size: 0.95rem;
        }
        
        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--gray-light);
            font-size: 0.85rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .post-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .read-more {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .read-more:hover {
            gap: 12px;
            color: var(--primary-dark);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 4rem;
        }
        
        .page-link {
            padding: 0.8rem 1.2rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .page-link:hover,
        .page-link.active {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .featured-post {
                grid-template-columns: 1fr;
            }
            
            .featured-image {
                min-height: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1.5rem;
            }
            
            .navbar {
                flex-direction: column;
                gap: 1.5rem;
                padding: 1.5rem 0;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .posts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .featured-title {
                font-size: 1.8rem;
            }
            
            .featured-content {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Debug info (remove in production) -->
    <div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 5px; font-size: 12px; z-index: 9999;">
        PHP Version: <?php echo phpversion(); ?><br>
        File: <?php echo basename(__FILE__); ?><br>
        DB: <?php echo $conn ? 'Connected' : 'Demo Mode'; ?>
    </div>

    <!-- Header -->
    <header id="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <i class="fas fa-code logo-icon"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
                <div class="nav-links">
                    <a href="#home" class="nav-link">Home</a>
                    <a href="#featured" class="nav-link">Featured</a>
                    <a href="#posts" class="nav-link">Blog</a>
                    <a href="#newsletter" class="nav-link">Newsletter</a>
                </div>
                <div class="nav-actions">
                    <button class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content animate__animated animate__fadeIn">
                <h1 class="hero-title">Welcome to <?php echo SITE_NAME; ?></h1>
                <p class="hero-subtitle">Latest technology insights, coding tutorials, and industry news from passionate developers around the world.</p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['total_posts']; ?></span>
                        <span class="stat-label">Articles</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($stats['total_views']); ?></span>
                        <span class="stat-label">Views</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['total_comments']; ?></span>
                        <span class="stat-label">Comments</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Post -->
    <section class="featured-section" id="featured">
        <div class="container">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Featured Article</h2>
                    <p class="section-subtitle">Our most popular and recent highlight</p>
                </div>
            </div>
            
            <?php if ($featured_post): ?>
            <div class="featured-post">
                <div class="featured-image-container">
                    <img src="<?php echo !empty($featured_post['image_path']) ? htmlspecialchars($featured_post['image_path']) : 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'; ?>" 
                         alt="<?php echo htmlspecialchars($featured_post['title']); ?>" class="featured-image">
                </div>
                <div class="featured-content">
                    <span class="featured-badge">Featured Post</span>
                    <h2 class="featured-title">
                        <a href="post.php?id=<?php echo $featured_post['id']; ?>">
                            <?php echo htmlspecialchars($featured_post['title']); ?>
                        </a>
                    </h2>
                    <p class="featured-excerpt">
                        <?php 
                        $excerpt = $featured_post['excerpt'] ?? $featured_post['content'] ?? '';
                        echo substr(strip_tags($excerpt), 0, 200) . '...'; 
                        ?>
                    </p>
                    <div class="post-meta">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($featured_post['author']); ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($featured_post['created_at'])); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo number_format($featured_post['views'] ?? 0); ?> views</span>
                        <span><i class="fas fa-comments"></i> 
                            <?php echo getCommentCount($conn, $featured_post['id']); ?> comments
                        </span>
                    </div>
                    <a href="post.php?id=<?php echo $featured_post['id']; ?>" class="read-more">
                        Read Full Article <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state" style="text-align: center; padding: 4rem; background: var(--lighter); border-radius: 20px;">
                <i class="fas fa-newspaper" style="font-size: 4rem; color: var(--border); margin-bottom: 1.5rem;"></i>
                <h3>No featured posts yet</h3>
                <p style="color: var(--gray);">Check back soon for featured content!</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Recent Posts -->
    <section class="posts-section" id="posts">
        <div class="container">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Latest Articles</h2>
                    <p class="section-subtitle">Stay updated with our newest content</p>
                </div>
            </div>
            
            <?php if (!empty($posts)): ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                    <article class="post-card animate__animated animate__fadeInUp">
                        <div class="post-image-container">
                            <?php if (!empty($post['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                            <?php else: ?>
                            <div style="background: var(--gradient-primary); height: 100%; display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-newspaper" style="font-size: 3rem;"></i>
                            </div>
                            <?php endif; ?>
                            <div class="post-category"><?php echo htmlspecialchars($post['author']); ?></div>
                        </div>
                        <div class="post-content">
                            <h3 class="post-title">
                                <a href="post.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            <p class="post-excerpt">
                                <?php 
                                $excerpt = $post['excerpt'] ?? $post['content'] ?? '';
                                echo substr(strip_tags($excerpt), 0, 120) . '...'; 
                                ?>
                            </p>
                            <div class="post-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                <span><i class="fas fa-eye"></i> <?php echo number_format($post['views'] ?? 0); ?></span>
                                <span><i class="fas fa-comments"></i> 
                                    <?php echo getCommentCount($conn, $post['id']); ?>
                                </span>
                            </div>
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="read-more">
                                Read More <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem; background: var(--lighter); border-radius: 20px; box-shadow: var(--shadow-md);">
                    <i class="fas fa-newspaper" style="font-size: 4rem; color: var(--border); margin-bottom: 1.5rem;"></i>
                    <h3>No articles yet</h3>
                    <p style="color: var(--gray);">We're working on new content. Stay tuned!</p>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Simple search functionality
        document.querySelector('.search-btn').addEventListener('click', function() {
            alert('Search functionality will be implemented soon!');
        });

        // Add hover effect to navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Check if page loaded successfully
        console.log('Page loaded successfully');
    </script>
</body>
</html>
<?php 
// Close connection if it exists
if ($conn) {
    $conn = null;
}
?>

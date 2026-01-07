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
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']);
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
$demo_mode = false;

// Try to create a database connection
try {
    // Azure SQL Database uses sqlsrv, not mysql
    $conn = new PDO("sqlsrv:Server=$host;Database=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // If connection fails, create demo mode
    $conn = null;
    $demo_mode = true;
    error_log("Database connection failed: " . $e->getMessage());
}

// Helper functions
function executeQuery($conn, $sql, $params = []) {
    global $demo_mode;
    
    if (!$conn && !$demo_mode) return false;
    
    try {
        if ($conn) {
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

function fetchSingle($conn, $sql, $params = []) {
    global $demo_mode;
    
    if (!$conn && $demo_mode) {
        // Demo data for testing
        return [
            'total_posts' => 2,
            'total_views' => 360,
            'total_comments' => 8
        ];
    }
    
    $stmt = executeQuery($conn, $sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

function fetchAll($conn, $sql, $params = []) {
    global $demo_mode;
    
    if (!$conn && $demo_mode) {
        // Demo data for testing
        return [
            [
                'id' => 1,
                'title' => 'Getting Started with PHP 8',
                'excerpt' => 'Learn the basics of PHP 8 and its new features',
                'author' => 'John Doe',
                'views' => 150,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'image_path' => null,
                'content' => 'Demo content...',
                'status' => 'published'
            ],
            [
                'id' => 2,
                'title' => 'React vs Vue: Which to Choose in 2024',
                'excerpt' => 'Comparison of two popular JavaScript frameworks',
                'author' => 'Jane Smith',
                'views' => 210,
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'image_path' => null,
                'content' => 'Demo content...',
                'status' => 'published'
            ]
        ];
    }
    
    $stmt = executeQuery($conn, $sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function getCommentCount($conn, $postId) {
    global $demo_mode;
    
    if (!$conn && $demo_mode) return 5; // Demo count
    
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
if ($page < 1) $page = 1;
$posts_per_page = 6;
$offset = ($page - 1) * $posts_per_page;

// Initialize stats with default values to prevent undefined key errors
$stats = [
    'total_posts' => 0,
    'total_views' => 0,
    'total_comments' => 0
];

// Get total posts count
$total_sql = "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'";
$total_result = fetchSingle($conn, $total_sql);
$total_posts = isset($total_result['total']) ? $total_result['total'] : 2;
$total_pages = ceil($total_posts / $posts_per_page);
if ($total_pages < 1) $total_pages = 1;

// Get posts for current page
if ($conn) {
    // For SQL Server (Azure)
    $posts_sql = "SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    $posts = fetchAll($conn, $posts_sql, [$offset, $posts_per_page]);
} else {
    // Demo mode
    $posts = fetchAll($conn, "");
}

// Get featured post
$featured_sql = "SELECT TOP 1 * FROM blog_posts WHERE status = 'published' AND image_path IS NOT NULL ORDER BY created_at DESC";
$featured_post = fetchSingle($conn, $featured_sql);

// Get popular posts
$popular_sql = "SELECT TOP 5 * FROM blog_posts WHERE status = 'published' ORDER BY views DESC";
$popular_posts = fetchAll($conn, $popular_sql);

// Get categories
$categories_sql = "SELECT TOP 6 author, COUNT(*) as post_count FROM blog_posts WHERE status = 'published' GROUP BY author ORDER BY post_count DESC";
$categories = fetchAll($conn, $categories_sql);

// Get total stats - FIXED: Ensure all keys exist
$stats_sql = "SELECT 
        COUNT(*) as total_posts,
        COALESCE(SUM(views), 0) as total_views,
        (SELECT COUNT(*) FROM comments WHERE status = 'approved') as total_comments
        FROM blog_posts WHERE status = 'published'";
$db_stats = fetchSingle($conn, $stats_sql);

if ($db_stats) {
    // Ensure all required keys exist
    $stats['total_posts'] = isset($db_stats['total_posts']) ? $db_stats['total_posts'] : 0;
    $stats['total_views'] = isset($db_stats['total_views']) ? $db_stats['total_views'] : 0;
    $stats['total_comments'] = isset($db_stats['total_comments']) ? $db_stats['total_comments'] : 0;
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
        /* Previous CSS styles remain the same until the end... */
        /* Add these new styles for footer and newsletter */
        
        /* Newsletter Section */
        .newsletter-section {
            background: var(--gradient-primary);
            color: white;
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .newsletter-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><path d="M0,0V100H1000V0C800,50 500,100 0,0Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: 100% 100px;
            background-position: bottom;
            opacity: 0.1;
        }
        
        .newsletter-container {
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .newsletter-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .newsletter-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .newsletter-form {
            display: flex;
            gap: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            outline: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .newsletter-btn {
            background: var(--darker);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .newsletter-btn:hover {
            background: var(--dark);
            transform: translateY(-2px);
        }
        
        /* Footer */
        footer {
            background: var(--darker);
            color: white;
            padding: 4rem 0 2rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .footer-logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        
        .footer-description {
            color: var(--gray-light);
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .footer-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: white;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-link {
            color: var(--gray-light);
            text-decoration: none;
            display: block;
            padding: 0.5rem 0;
            transition: all 0.3s ease;
        }
        
        .footer-link:hover {
            color: var(--primary-light);
            padding-left: 10px;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: var(--gray-light);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .newsletter-form {
                flex-direction: column;
            }
            
            .newsletter-title {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .footer-content {
                grid-template-columns: 1fr;
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

    <!-- Newsletter Section -->
    <section class="newsletter-section" id="newsletter">
        <div class="container">
            <div class="newsletter-container">
                <h2 class="newsletter-title">Subscribe to Our Newsletter</h2>
                <p class="newsletter-subtitle">Stay updated with the latest tech news, tutorials, and exclusive content delivered directly to your inbox.</p>
                <form class="newsletter-form" action="subscribe.php" method="POST">
                    <input type="email" name="email" class="newsletter-input" placeholder="Enter your email address" required>
                    <button type="submit" class="newsletter-btn">Subscribe</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <a href="index.php" class="footer-logo">
                        <i class="fas fa-code"></i>
                        <span><?php echo SITE_NAME; ?></span>
                    </a>
                    <p class="footer-description">
                        Tech Blog Pro brings you the latest in technology, programming tutorials, 
                        and industry insights from passionate developers worldwide.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php" class="footer-link">Home</a></li>
                        <li><a href="about.php" class="footer-link">About Us</a></li>
                        <li><a href="contact.php" class="footer-link">Contact</a></li>
                        <li><a href="privacy.php" class="footer-link">Privacy Policy</a></li>
                        <li><a href="terms.php" class="footer-link">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-title">Categories</h3>
                    <ul class="footer-links">
                        <li><a href="category.php?cat=php" class="footer-link">PHP Development</a></li>
                        <li><a href="category.php?cat=javascript" class="footer-link">JavaScript</a></li>
                        <li><a href="category.php?cat=python" class="footer-link">Python</a></li>
                        <li><a href="category.php?cat=webdev" class="footer-link">Web Development</a></li>
                        <li><a href="category.php?cat=ai" class="footer-link">Artificial Intelligence</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-title">Contact Info</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope" style="margin-right: 10px;"></i> <?php echo ADMIN_EMAIL; ?></li>
                        <li><i class="fas fa-globe" style="margin-right: 10px;"></i> <?php echo SITE_URL; ?></li>
                        <li><i class="fas fa-clock" style="margin-right: 10px;"></i> Mon-Fri: 9AM-6PM</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

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

        // Newsletter form submission
        document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[name="email"]').value;
            if (email) {
                alert('Thank you for subscribing! Check your email for confirmation.');
                this.reset();
            }
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

<?php
require_once 'config.php';

// Get pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$posts_per_page = 6;
$offset = ($page - 1) * $posts_per_page;

try {
    // Get total posts count
    $total_query = "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'";
    $stmt = $conn->prepare($total_query);
    $stmt->execute();
    $total_posts = $stmt->fetch()['total'];
    $total_pages = ceil($total_posts / $posts_per_page);

    // Get posts for current page - SQL Server compatible (using OFFSET FETCH)
    $query = "SELECT * FROM blog_posts WHERE status = 'published' 
              ORDER BY created_at DESC 
              OFFSET :offset ROWS 
              FETCH NEXT :limit ROWS ONLY";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    // Get featured post - SQL Server compatible (using TOP)
    $featured_query = "SELECT TOP 1 * FROM blog_posts WHERE status = 'published' AND image_path IS NOT NULL ORDER BY created_at DESC";
    $stmt = $conn->prepare($featured_query);
    $stmt->execute();
    $featured_post = $stmt->fetch();

    // Get most popular posts (by views) - SQL Server compatible (using TOP)
    $popular_query = "SELECT TOP 5 * FROM blog_posts WHERE status = 'published' ORDER BY views DESC";
    $stmt = $conn->prepare($popular_query);
    $stmt->execute();
    $popular_posts = $stmt->fetchAll();

    // Get categories (distinct authors as categories for now) - SQL Server compatible (using TOP)
    $categories_query = "SELECT TOP 6 author, COUNT(*) as post_count 
                        FROM blog_posts 
                        WHERE status = 'published' 
                        GROUP BY author 
                        ORDER BY post_count DESC";
    $stmt = $conn->prepare($categories_query);
    $stmt->execute();
    $categories = $stmt->fetchAll();

    // Get total stats
    $stats_query = "SELECT 
                    COUNT(*) as total_posts,
                    SUM(views) as total_views,
                    (SELECT COUNT(*) FROM comments WHERE status = 'approved') as total_comments
                    FROM blog_posts WHERE status = 'published'";
    $stmt = $conn->prepare($stats_query);
    $stmt->execute();
    $stats = $stmt->fetch() ?: ['total_posts' => 0, 'total_views' => 0, 'total_comments' => 0];

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
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
        /* Your existing CSS styles here - they remain unchanged */
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
        
        /* [Include all your existing CSS styles here - they remain unchanged] */
        
    </style>
</head>
<body>
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
                    <a href="#categories" class="nav-link">Categories</a>
                    <a href="#popular" class="nav-link">Popular</a>
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
                    <img src="<?php echo $featured_post['image_path'] ?? 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'; ?>" 
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
                        <?php echo substr(strip_tags($featured_post['excerpt'] ?? ''), 0, 200) . '...'; ?>
                    </p>
                    <div class="post-meta">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($featured_post['author']); ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($featured_post['created_at'])); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo number_format($featured_post['views']); ?> views</span>
                        <span><i class="fas fa-comments"></i> 
                            <?php 
                            try {
                                $comment_count_query = "SELECT COUNT(*) as count FROM comments WHERE post_id = :post_id AND status = 'approved'";
                                $stmt = $conn->prepare($comment_count_query);
                                $stmt->execute([':post_id' => $featured_post['id']]);
                                $comment_count = $stmt->fetch()['count'];
                            } catch (PDOException $e) {
                                $comment_count = 0;
                            }
                            echo $comment_count;
                            ?> comments
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
            
            <?php if (count($posts) > 0): ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                    <article class="post-card animate__animated animate__fadeInUp">
                        <div class="post-image-container">
                            <?php if ($post['image_path']): ?>
                            <img src="<?php echo $post['image_path']; ?>" 
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
                                <?php echo substr(strip_tags($post['excerpt'] ?? ''), 0, 120) . '...'; ?>
                            </p>
                            <div class="post-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                <span><i class="fas fa-eye"></i> <?php echo number_format($post['views']); ?></span>
                                <span><i class="fas fa-comments"></i> 
                                    <?php 
                                    try {
                                        $comment_count_query = "SELECT COUNT(*) as count FROM comments WHERE post_id = :post_id AND status = 'approved'";
                                        $stmt = $conn->prepare($comment_count_query);
                                        $stmt->execute([':post_id' => $post['id']]);
                                        $comment_count = $stmt->fetch()['count'];
                                    } catch (PDOException $e) {
                                        $comment_count = 0;
                                    }
                                    echo $comment_count;
                                    ?>
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

    <!-- Categories Section -->
    <section class="categories-section" id="categories">
        <div class="container">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Explore Categories</h2>
                    <p class="section-subtitle">Browse content by topic</p>
                </div>
            </div>
            
            <div class="categories-grid">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <h3 class="category-name"><?php echo htmlspecialchars($category['author']); ?></h3>
                        <p class="category-count"><?php echo $category['post_count']; ?> Articles</p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                        <i class="fas fa-tags" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                        <h3>No categories yet</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Popular Posts -->
    <section class="popular-section" id="popular">
        <div class="container">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Most Popular</h2>
                    <p class="section-subtitle">Trending articles this week</p>
                </div>
            </div>
            
            <div class="popular-posts">
                <?php if (!empty($popular_posts)): ?>
                    <?php foreach ($popular_posts as $index => $popular_post): ?>
                    <div class="popular-post">
                        <div class="popular-rank">#<?php echo $index + 1; ?></div>
                        <div class="popular-content">
                            <h4>
                                <a href="post.php?id=<?php echo $popular_post['id']; ?>">
                                    <?php echo htmlspecialchars($popular_post['title']); ?>
                                </a>
                            </h4>
                            <div class="popular-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($popular_post['author']); ?></span>
                                <span><i class="fas fa-eye"></i> <?php echo number_format($popular_post['views']); ?> views</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: var(--gray);">
                        <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>No popular posts yet</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter-section" id="newsletter">
        <div class="container">
            <div class="newsletter-content">
                <h2 class="newsletter-title">Stay Updated</h2>
                <p class="newsletter-subtitle">Subscribe to our newsletter and never miss an update. Get the latest tech news, tutorials, and insights delivered to your inbox.</p>
                <form class="newsletter-form">
                    <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
                    <button type="submit" class="newsletter-btn">Subscribe</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <div class="footer-logo"><?php echo SITE_NAME; ?></div>
                    <p>Sharing knowledge and insights about technology, programming, and innovation with developers worldwide.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="#featured"><i class="fas fa-chevron-right"></i> Featured</a></li>
                        <li><a href="#posts"><i class="fas fa-chevron-right"></i> Blog</a></li>
                        <li><a href="#categories"><i class="fas fa-chevron-right"></i> Categories</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Documentation</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Tutorials</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Code Examples</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Community</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Contact</h3>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-envelope"></i> contact@example.com</a></li>
                        <li><a href="#"><i class="fas fa-phone"></i> +1 (555) 123-4567</a></li>
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> San Francisco, CA</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            const backToTop = document.getElementById('backToTop');
            
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            // Show/hide back to top button
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
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

        // Back to top functionality
        document.getElementById('backToTop').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Newsletter form submission
        document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('.newsletter-input').value;
            if (email) {
                alert('Thank you for subscribing to our newsletter!');
                this.querySelector('.newsletter-input').value = '';
            }
        });

        // Search functionality
        document.querySelector('.search-btn').addEventListener('click', function() {
            alert('Search functionality will be implemented soon!');
        });

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                }
            });
        }, observerOptions);

        // Observe all post cards and category cards
        document.querySelectorAll('.post-card, .category-card, .popular-post').forEach(el => {
            observer.observe(el);
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
    </script>
</body>
</html>
<?php 
// PDO connection closes automatically when $conn is destroyed
// No need to explicitly close it
?>

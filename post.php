<?php
require_once 'config.php';

// Get post ID from URL
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Fetch the post
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ? AND status = 'published'");
    $stmt->execute([$post_id]);
    
    if ($stmt->rowCount() == 0) {
        header('Location: index.php');
        exit();
    }
    
    $post = $stmt->fetch();
    
    // Increment view count
    $stmt = $conn->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
    
} catch (PDOException $e) {
    die("Error loading post: " . $e->getMessage());
}

// Handle comment submission
$comment_success = '';
$comment_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_comment'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $comment_content = $_POST['comment'] ?? '';
    
    // Validate required fields
    if (!empty($name) && !empty($comment_content)) {
        try {
            $stmt = $conn->prepare("INSERT INTO comments (post_id, author, email, content, status) 
                                   VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$post_id, $name, $email, $comment_content]);
            
            $comment_success = "Thank you for your comment! It will be visible after moderation.";
            
            // Clear form fields
            $_POST['name'] = '';
            $_POST['email'] = '';
            $_POST['comment'] = '';
            
        } catch (PDOException $e) {
            $comment_error = "Error submitting comment: " . $e->getMessage();
        }
    } else {
        $comment_error = "Please fill in all required fields (Name and Comment).";
    }
}

// Fetch approved comments for this post
try {
    $stmt = $conn->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {
    $comments = [];
}

// Get related posts (same author or similar tags)
try {
    $stmt = $conn->prepare("SELECT TOP 3 * FROM blog_posts WHERE author = ? AND id != ? AND status = 'published' ORDER BY created_at DESC");
    $stmt->execute([$post['author'], $post_id]);
    $related_posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $related_posts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']) . ' - ' . SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=JetBrains+Mono:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --border: #e5e7eb;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            line-height: 1.6;
            background: var(--light);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Header */
        header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .admin-btn {
            background: var(--primary);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .admin-btn:hover {
            background: var(--primary-dark);
        }
        
        /* Post Content */
        .post-header {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            margin: 2rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .post-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--dark);
            line-height: 1.3;
        }
        
        .post-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .post-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .featured-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 10px;
            margin: 2rem 0;
        }
        
        .post-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--dark);
        }
        
        .post-content h2 {
            font-size: 1.8rem;
            margin: 2rem 0 1rem;
            color: var(--dark);
        }
        
        .post-content p {
            margin-bottom: 1.5rem;
        }
        
        .post-content ul, .post-content ol {
            margin-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .post-content li {
            margin-bottom: 0.5rem;
        }
        
        .post-content code {
            background: #f3f4f6;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
        }
        
        .post-content pre {
            background: #1f2937;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            margin: 1.5rem 0;
            font-family: 'JetBrains Mono', monospace;
        }
        
        /* Comments Section */
        .comments-section {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            margin: 2rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            color: var(--dark);
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }
        
        .comment-form {
            background: var(--light);
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 3rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn {
            padding: 0.8rem 2rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: var(--primary-dark);
        }
        
        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        /* Comments List */
        .comment {
            padding: 1.5rem;
            background: var(--light);
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .comment-author {
            font-weight: 600;
            color: var(--dark);
        }
        
        .comment-date {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .comment-content {
            line-height: 1.6;
        }
        
        /* Related Posts */
        .related-posts {
            margin: 3rem 0;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .related-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .related-card:hover {
            transform: translateY(-5px);
        }
        
        .related-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .related-content {
            padding: 1.5rem;
        }
        
        .related-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .related-title a {
            color: var(--dark);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .related-title a:hover {
            color: var(--primary);
        }
        
        .related-excerpt {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 3rem 0;
            text-align: center;
            margin-top: 3rem;
        }
        
        .footer-content {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .footer-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            font-family: 'JetBrains Mono', monospace;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .copyright {
            color: rgba(255,255,255,0.6);
            font-size: 0.9rem;
            margin-top: 2rem;
        }
        
        /* Back to top */
        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--primary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .back-to-top:hover {
            background: var(--primary-dark);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .post-header,
            .comments-section {
                padding: 2rem 1rem;
            }
            
            .post-title {
                font-size: 2rem;
            }
            
            .post-meta {
                flex-direction: column;
                gap: 1rem;
            }
            
            .related-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }
            
            .post-title {
                font-size: 1.8rem;
            }
            
            .comment-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <i class="fas fa-code"></i> <?php echo SITE_NAME; ?>
                </a>
                <div class="nav-links">
                    <a href="index.php">Home</a>
                    <a href="#comments">Comments</a>
                    <a href="admin-login.php" class="admin-btn">
                        <i class="fas fa-lock"></i> Admin
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <!-- Post Content -->
        <article class="post-header">
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <div class="post-meta">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author']); ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                <span><i class="fas fa-eye"></i> <?php echo ($post['views'] ?? 0) + 1; ?> views</span>
                <span><i class="fas fa-comments"></i> <?php echo count($comments); ?> comments</span>
            </div>
            
            <?php if (!empty($post['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                     class="featured-image">
            <?php endif; ?>
            
            <div class="post-content">
                <?php echo nl2br($post['content']); ?>
            </div>
        </article>

        <!-- Comments Section -->
        <section class="comments-section" id="comments">
            <h2 class="section-title">Comments (<?php echo count($comments); ?>)</h2>
            
            <!-- Comment Form -->
            <div class="comment-form">
                <h3 style="margin-bottom: 1.5rem; color: var(--dark);">Leave a Comment</h3>
                
                <?php if ($comment_success): ?>
                    <div class="alert success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $comment_success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($comment_error): ?>
                    <div class="alert error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $comment_error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               placeholder="Your name">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               placeholder="Your email (optional)">
                    </div>
                    
                    <div class="form-group">
                        <label for="comment">Comment *</label>
                        <textarea id="comment" name="comment" required 
                                  placeholder="Write your comment here..."><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="submit_comment" class="btn">
                        <i class="fas fa-paper-plane"></i> Post Comment
                    </button>
                </form>
            </div>
            
            <!-- Comments List -->
            <?php if (empty($comments)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--gray);">
                    <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>No comments yet</h3>
                    <p>Be the first to share your thoughts!</p>
                </div>
            <?php else: ?>
                <div class="comments-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                            <div class="comment-header">
                                <div class="comment-author">
                                    <i class="fas fa-user"></i> 
                                    <?php echo htmlspecialchars($comment['author'] ?? 'Anonymous'); ?>
                                </div>
                                <div class="comment-date">
                                    <?php echo date('F j, Y \a\t g:i a', strtotime($comment['created_at'] ?? 'now')); ?>
                                </div>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'] ?? '')); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Related Posts -->
        <?php if (!empty($related_posts)): ?>
            <section class="related-posts">
                <h2 class="section-title">Related Posts</h2>
                <div class="related-grid">
                    <?php foreach ($related_posts as $related): ?>
                        <article class="related-card">
                            <?php if (!empty($related['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($related['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                     class="related-image">
                            <?php else: ?>
                                <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); height: 200px; display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-newspaper" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="related-content">
                                <h3 class="related-title">
                                    <a href="post.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h3>
                                <p class="related-excerpt">
                                    <?php echo substr(strip_tags($related['excerpt'] ?? ''), 0, 100) . '...'; ?>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Back to Top -->
    <a href="#" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo"><?php echo SITE_NAME; ?></div>
                <p>Sharing knowledge and insights about technology, programming, and innovation.</p>
                
                <div class="footer-links">
                    <a href="index.php">Home</a>
                    <a href="#comments">Blog</a>
                    <a href="admin-login.php">Admin</a>
                    <a href="#">Contact</a>
                </div>
                
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Back to top button
        document.addEventListener('DOMContentLoaded', function() {
            const backToTop = document.querySelector('.back-to-top');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTop.style.display = 'flex';
                } else {
                    backToTop.style.display = 'none';
                }
            });
            
            backToTop.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 500);
                });
            }, 5000);
            
            // Smooth scroll to comments section
            const commentLinks = document.querySelectorAll('a[href="#comments"]');
            commentLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const commentsSection = document.getElementById('comments');
                    if (commentsSection) {
                        commentsSection.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
session_start();

// PDO Database Connection
try {
    $conn = new PDO("sqlsrv:server = tcp:tbserver2025.database.windows.net,1433; Database = if0_40840685_tech_blog", "CloudSA219c14b7", "{your_password_here}");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Set default fetch mode
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit();
}

$message = '';
$error = '';
$edit_mode = false;
$current_post = null;
$total_posts = 0;
$total_comments = 0;
$pending_comments = 0;
$total_views = 0;
$total_authors = 0;

// Get statistics using PDO
try {
    // Get total posts
    $stmt = $conn->query("SELECT COUNT(*) as count FROM blog_posts");
    $total_posts_row = $stmt->fetch();
    $total_posts = $total_posts_row['count'];

    // Get total comments
    $stmt = $conn->query("SELECT COUNT(*) as count FROM comments");
    $comments_count_row = $stmt->fetch();
    $total_comments = $comments_count_row['count'];

    // Get pending comments
    $stmt = $conn->query("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'");
    $pending_comments_row = $stmt->fetch();
    $pending_comments = $pending_comments_row['count'];

    // Get total views
    $stmt = $conn->query("SELECT SUM(views) as total FROM blog_posts");
    $views_row = $stmt->fetch();
    $total_views = $views_row['total'] ?? 0;

    // Get total authors
    $stmt = $conn->query("SELECT COUNT(DISTINCT author) as count FROM blog_posts");
    $authors_row = $stmt->fetch();
    $total_authors = $authors_row['count'];

} catch (PDOException $e) {
    $error = "Error fetching statistics: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ADD or UPDATE post
    if (isset($_POST['action']) && ($_POST['action'] == 'add_post' || $_POST['action'] == 'update_post')) {
        $title = $_POST['title'] ?? '';
        $excerpt = $_POST['excerpt'] ?? '';
        $content = $_POST['content'] ?? '';
        $author = $_POST['author'] ?? 'Admin';
        
        // Handle image upload
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['image']['type'];
            $file_size = $_FILES['image']['size'];
            
            if (in_array($file_type, $allowed_types) && $file_size <= 2097152) { // 2MB max
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                $target_path = 'uploads/' . $file_name;
                
                // Create uploads directory if it doesn't exist
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0755, true);
                }
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    $image_path = $target_path;
                }
            }
        }
        
        try {
            if ($_POST['action'] == 'add_post') {
                // Add new post
                $query = "INSERT INTO blog_posts (title, excerpt, content, author, image_path, status) 
                          VALUES (:title, :excerpt, :content, :author, :image_path, 'published')";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':title' => $title,
                    ':excerpt' => $excerpt,
                    ':content' => $content,
                    ':author' => $author,
                    ':image_path' => $image_path
                ]);
                $message = "Post added successfully!";
                
            } else {
                // Update existing post
                $post_id = intval($_POST['post_id']);
                
                // If new image uploaded, update image_path
                if ($image_path) {
                    $query = "UPDATE blog_posts SET 
                              title = :title,
                              excerpt = :excerpt,
                              content = :content,
                              author = :author,
                              image_path = :image_path
                              WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':title' => $title,
                        ':excerpt' => $excerpt,
                        ':content' => $content,
                        ':author' => $author,
                        ':image_path' => $image_path,
                        ':id' => $post_id
                    ]);
                } else {
                    $query = "UPDATE blog_posts SET 
                              title = :title,
                              excerpt = :excerpt,
                              content = :content,
                              author = :author
                              WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':title' => $title,
                        ':excerpt' => $excerpt,
                        ':content' => $content,
                        ':author' => $author,
                        ':id' => $post_id
                    ]);
                }
                $message = "Post updated successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // DELETE post
    if (isset($_POST['action']) && $_POST['action'] == 'delete_post' && isset($_POST['post_id'])) {
        $post_id = intval($_POST['post_id']);
        
        try {
            // Get image path before deleting
            $stmt = $conn->prepare("SELECT image_path FROM blog_posts WHERE id = :id");
            $stmt->execute([':id' => $post_id]);
            $row = $stmt->fetch();
            
            // Delete the image file if exists
            if ($row && !empty($row['image_path']) && file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
            
            // Delete the post
            $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = :id");
            $stmt->execute([':id' => $post_id]);
            $message = "Post deleted successfully!";
            
        } catch (PDOException $e) {
            $error = "Error deleting post: " . $e->getMessage();
        }
    }
    
    // APPROVE comment
    if (isset($_POST['action']) && $_POST['action'] == 'approve_comment' && isset($_POST['comment_id'])) {
        $comment_id = intval($_POST['comment_id']);
        
        try {
            $stmt = $conn->prepare("UPDATE comments SET status = 'approved' WHERE id = :id");
            $stmt->execute([':id' => $comment_id]);
            $message = "Comment approved successfully!";
        } catch (PDOException $e) {
            $error = "Error approving comment: " . $e->getMessage();
        }
    }
    
    // REJECT comment
    if (isset($_POST['action']) && $_POST['action'] == 'reject_comment' && isset($_POST['comment_id'])) {
        $comment_id = intval($_POST['comment_id']);
        
        try {
            $stmt = $conn->prepare("UPDATE comments SET status = 'spam' WHERE id = :id");
            $stmt->execute([':id' => $comment_id]);
            $message = "Comment rejected and marked as spam!";
        } catch (PDOException $e) {
            $error = "Error rejecting comment: " . $e->getMessage();
        }
    }
    
    // DELETE comment
    if (isset($_POST['action']) && $_POST['action'] == 'delete_comment' && isset($_POST['comment_id'])) {
        $comment_id = intval($_POST['comment_id']);
        
        try {
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = :id");
            $stmt->execute([':id' => $comment_id]);
            $message = "Comment deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting comment: " . $e->getMessage();
        }
    }
    
    // BULK comment actions
    if (isset($_POST['bulk_action']) && isset($_POST['selected_comments'])) {
        $selected_ids = array_map('intval', $_POST['selected_comments']);
        
        try {
            switch ($_POST['bulk_action']) {
                case 'approve':
                    $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                    $stmt = $conn->prepare("UPDATE comments SET status = 'approved' WHERE id IN ($placeholders)");
                    $stmt->execute($selected_ids);
                    $message = "Comments approved successfully!";
                    break;
                    
                case 'reject':
                    $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                    $stmt = $conn->prepare("UPDATE comments SET status = 'spam' WHERE id IN ($placeholders)");
                    $stmt->execute($selected_ids);
                    $message = "Comments rejected successfully!";
                    break;
                    
                case 'delete':
                    $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                    $stmt = $conn->prepare("DELETE FROM comments WHERE id IN ($placeholders)");
                    $stmt->execute($selected_ids);
                    $message = "Comments deleted successfully!";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Error performing bulk action: " . $e->getMessage();
        }
    }
}

// Check if edit mode
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = :id");
        $stmt->execute([':id' => $edit_id]);
        
        if ($stmt->rowCount() > 0) {
            $edit_mode = true;
            $current_post = $stmt->fetch();
        }
    } catch (PDOException $e) {
        $error = "Error fetching post: " . $e->getMessage();
    }
}

// Fetch all posts
$posts = [];
try {
    $stmt = $conn->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching posts: " . $e->getMessage();
}

// Get all comments with post info for management
$all_comments = [];
try {
    $stmt = $conn->query("SELECT c.*, p.title as post_title FROM comments c 
                         LEFT JOIN blog_posts p ON c.post_id = p.id 
                         ORDER BY c.created_at DESC");
    $all_comments = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching comments: " . $e->getMessage();
}

// Define SITE_NAME constant if not already defined
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'TechBlog');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=JetBrains+Mono:wght@300;400&display=swap" rel="stylesheet">
    <!-- SimpleMDE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
    <style>
        /* Your CSS styles remain the same */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --border: #e5e7eb;
            --sidebar-bg: #111827;
            --card-bg: #ffffff;
            --hover-bg: #f3f4f6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light);
            min-height: 100vh;
            color: var(--dark);
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--sidebar-bg);
            color: white;
            padding: 1.5rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
        }

        .logo-icon {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .logo-text {
            font-size: 1.6rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            font-family: 'JetBrains Mono', monospace;
        }

        .nav-links {
            padding: 0 1.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.9rem 1rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(99, 102, 241, 0.2);
            color: white;
        }

        .nav-item i {
            width: 20px;
            text-align: center;
        }

        .badge {
            background: var(--primary);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            margin-left: auto;
        }

        .badge-warning {
            background: var(--warning);
        }

        .user-info {
            padding: 1.5rem;
            background: rgba(255,255,255,0.05);
            margin: 2rem 1.5rem 0;
            border-radius: 12px;
        }

        .user-info h4 {
            color: white;
            margin-bottom: 0.3rem;
        }

        .user-info p {
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.2rem 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .admin-header h1 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .admin-header p {
            color: var(--gray);
        }

        /* Dashboard Stats */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.info {
            border-left-color: var(--info);
        }

        .stat-card.danger {
            border-left-color: var(--danger);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .stat-icon.success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
        }

        .stat-icon.info {
            background: linear-gradient(135deg, var(--info) 0%, #1d4ed8 100%);
        }

        .stat-icon.danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
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
            border-left: 4px solid var(--success);
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        /* Form Styles */
        .post-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .post-form h2 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        input[type="text"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .file-upload {
            border: 2px dashed var(--border);
            padding: 1.5rem;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: var(--primary);
        }

        .current-image {
            margin-top: 1rem;
        }

        .current-image img {
            max-width: 200px;
            border-radius: 8px;
        }

        /* Button Styles */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #0da271;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-2px);
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }

        /* Posts Table */
        .posts-list {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .posts-list h2 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .post-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--hover-bg);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .post-info h4 {
            font-size: 1.1rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .post-meta {
            display: flex;
            gap: 1.5rem;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .post-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .post-actions {
            display: flex;
            gap: 0.8rem;
        }

        /* Comments Management */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .posts-table {
            width: 100%;
            border-collapse: collapse;
        }

        .posts-table th {
            background: var(--hover-bg);
            padding: 1.2rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid var(--border);
        }

        .posts-table td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        .posts-table tbody tr:hover {
            background: var(--hover-bg);
        }

        .actions-cell {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-spam {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        /* Bulk Actions */
        .bulk-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--hover-bg);
            border-radius: 8px;
        }

        .bulk-select {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .comment-content {
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .comment-author {
            font-weight: 500;
            color: var(--dark);
        }

        .comment-email {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 0.2rem;
        }

        .comment-post {
            font-size: 0.9rem;
            color: var(--primary);
            margin-top: 0.5rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .post-item {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .post-actions {
                width: 100%;
                justify-content: center;
            }
            
            .posts-table {
                display: block;
                overflow-x: auto;
            }
            
            .actions-cell {
                flex-direction: column;
            }
            
            .bulk-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-rocket logo-icon"></i>
                <a href="index.php" class="logo-text">TechBlog</a>
            </div>
            
            <nav class="nav-links">
                <a href="admin-panel.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="admin-panel.php" class="nav-item">
                    <i class="fas fa-newspaper"></i>
                    All Posts
                    <span class="badge"><?php echo $total_posts; ?></span>
                </a>
                <a href="admin-panel.php#comments" class="nav-item">
                    <i class="fas fa-comments"></i>
                    Comments
                    <span class="badge <?php echo $pending_comments > 0 ? 'badge-warning' : ''; ?>">
                        <?php echo $total_comments; ?>
                        <?php if ($pending_comments > 0): ?>
                            (<?php echo $pending_comments; ?> pending)
                        <?php endif; ?>
                    </span>
                </a>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-eye"></i>
                    View Blog
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </nav>
            
            <div class="user-info">
                <h4><?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></h4>
                <p><?php echo $_SESSION['admin_role'] ?? 'Administrator'; ?></p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="admin-header">
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <?php echo $_SESSION['admin_name'] ?? 'Admin'; ?>!</p>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $total_posts; ?></div>
                            <div class="stat-label">Total Posts</div>
                        </div>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-icon success">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo number_format($total_views); ?></div>
                            <div class="stat-label">Total Views</div>
                        </div>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-icon info">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $total_comments; ?></div>
                            <div class="stat-label">Total Comments</div>
                        </div>
                    </div>
                </div>

                <div class="stat-card <?php echo $pending_comments > 0 ? 'warning' : 'success'; ?>">
                    <div class="stat-header">
                        <div class="stat-icon <?php echo $pending_comments > 0 ? 'warning' : 'success'; ?>">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $pending_comments; ?></div>
                            <div class="stat-label">Pending Comments</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Post Form -->
            <section class="post-form">
                <h2>
                    <i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus-circle'; ?>"></i>
                    <?php echo $edit_mode ? 'Edit Blog Post' : 'Create New Post'; ?>
                </h2>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_post' : 'add_post'; ?>">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="post_id" value="<?php echo $current_post['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title"><i class="fas fa-heading"></i> Post Title *</label>
                            <input type="text" id="title" name="title" required 
                                   value="<?php echo $edit_mode ? htmlspecialchars($current_post['title']) : ''; ?>"
                                   placeholder="Enter post title...">
                        </div>
                        
                        <div class="form-group">
                            <label for="author"><i class="fas fa-user"></i> Author</label>
                            <input type="text" id="author" name="author" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($current_post['author']) : ($_SESSION['admin_name'] ?? 'Admin'); ?>"
                                   placeholder="Author name...">
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="image"><i class="fas fa-image"></i> Featured Image</label>
                            <div class="file-upload" onclick="document.getElementById('image').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload image</p>
                                <small>Max 2MB. Supported: JPG, PNG, GIF, WebP</small>
                            </div>
                            <input type="file" id="image" name="image" accept="image/*" style="display: none;">
                            
                            <?php if ($edit_mode && !empty($current_post['image_path'])): ?>
                                <div class="current-image">
                                    <p><strong>Current Image:</strong></p>
                                    <img src="<?php echo htmlspecialchars($current_post['image_path']); ?>" 
                                         alt="Current featured image">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="excerpt"><i class="fas fa-quote-left"></i> Short Excerpt</label>
                            <textarea id="excerpt" name="excerpt" rows="3" placeholder="Brief description of your post..."><?php 
                                echo $edit_mode ? htmlspecialchars($current_post['excerpt']) : '';
                            ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="content"><i class="fas fa-edit"></i> Content *</label>
                        <textarea id="content" name="content" rows="10" required><?php 
                            echo $edit_mode ? htmlspecialchars($current_post['content']) : '';
                        ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-<?php echo $edit_mode ? 'save' : 'paper-plane'; ?>"></i>
                            <?php echo $edit_mode ? 'Update Post' : 'Publish Post'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="admin-panel.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
            
            <!-- Existing Posts -->
            <section class="posts-list">
                <h2>
                    <i class="fas fa-list"></i>
                    All Blog Posts (<?php echo $total_posts; ?>)
                </h2>
                
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-newspaper"></i>
                        <h3>No posts yet</h3>
                        <p>Create your first blog post using the form above.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-item">
                            <div class="post-info">
                                <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                                <div class="post-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                    <span><i class="fas fa-eye"></i> <?php echo $post['views']; ?> views</span>
                                    <span><i class="fas fa-comment"></i> 
                                        <?php 
                                            // Get comment count for this post using PDO
                                            try {
                                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = :post_id");
                                                $stmt->execute([':post_id' => $post['id']]);
                                                $comment_count_row = $stmt->fetch();
                                                echo $comment_count_row['count'];
                                            } catch (PDOException $e) {
                                                echo '0';
                                            }
                                        ?> comments
                                    </span>
                                </div>
                            </div>
                            <div class="post-actions">
                                <a href="admin-panel.php?edit=<?php echo $post['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_post">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-success" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Comments Management -->
            <section class="posts-list" id="comments">
                <h2>
                    <i class="fas fa-comments"></i>
                    Comments Management (<?php echo $total_comments; ?> total, <?php echo $pending_comments; ?> pending)
                </h2>

                <!-- Bulk Actions -->
                <form method="POST" action="" id="bulkForm">
                    <div class="bulk-actions">
                        <div class="bulk-select">
                            <input type="checkbox" id="selectAll">
                            <label for="selectAll">Select All</label>
                            <span id="selectedCount">0 selected</span>
                        </div>
                        
                        <select name="bulk_action" id="bulkAction" class="form-control" style="padding: 0.5rem; border-radius: 5px; border: 1px solid var(--border);">
                            <option value="">Bulk Actions</option>
                            <option value="approve">Approve Selected</option>
                            <option value="reject">Reject as Spam</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary" id="applyBulkAction" disabled>
                            <i class="fas fa-play"></i> Apply
                        </button>
                    </div>

                    <div class="table-container">
                        <table class="posts-table">
                            <thead>
                                <tr>
                                    <th width="50"><input type="checkbox" id="selectAllHeader"></th>
                                    <th width="200">Comment Details</th>
                                    <th>Comment Content</th>
                                    <th width="150">Status</th>
                                    <th width="150">Date</th>
                                    <th width="200">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_comments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center" style="text-align: center; padding: 3rem;">
                                            <i class="fas fa-comment-slash" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                                            <h3>No comments yet</h3>
                                            <p>Comments will appear here once users start commenting on your posts.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_comments as $comment): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_comments[]" 
                                                       value="<?php echo $comment['id']; ?>" 
                                                       class="comment-checkbox">
                                            </td>
                                            <td>
                                                <div class="comment-author">
                                                    <i class="fas fa-user"></i> 
                                                    <?php echo htmlspecialchars($comment['name'] ?? $comment['author'] ?? 'Anonymous'); ?>
                                                </div>
                                                <div class="comment-email">
                                                    <i class="fas fa-envelope"></i> 
                                                    <?php echo htmlspecialchars($comment['email'] ?? 'No email'); ?>
                                                </div>
                                                <div class="comment-post">
                                                    <i class="fas fa-link"></i> 
                                                    <?php echo htmlspecialchars($comment['post_title'] ?? 'Unknown Post'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="comment-content">
                                                    <?php echo nl2br(htmlspecialchars(substr($comment['content'] ?? '', 0, 200))); ?>
                                                    <?php if (isset($comment['content']) && strlen($comment['content']) > 200): ?>...<?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $status_class = '';
                                                    switch ($comment['status'] ?? 'pending') {
                                                        case 'approved':
                                                            $status_class = 'status-approved';
                                                            break;
                                                        case 'pending':
                                                            $status_class = 'status-pending';
                                                            break;
                                                        case 'spam':
                                                            $status_class = 'status-spam';
                                                            break;
                                                        default:
                                                            $status_class = 'status-pending';
                                                    }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 0.3rem;"></i>
                                                    <?php echo ucfirst($comment['status'] ?? 'pending'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($comment['created_at'] ?? 'now')); ?>
                                                <br>
                                                <small style="color: var(--gray);">
                                                    <?php echo date('H:i', strtotime($comment['created_at'] ?? 'now')); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="actions-cell">
                                                    <?php if (($comment['status'] ?? 'pending') != 'approved'): ?>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="action" value="approve_comment">
                                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (($comment['status'] ?? 'pending') != 'spam'): ?>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="action" value="reject_comment">
                                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-warning" 
                                                                    onclick="return confirm('Mark this comment as spam?')">
                                                                <i class="fas fa-ban"></i> Reject
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_comment">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this comment?')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                    
                                                    <?php if (isset($comment['post_id'])): ?>
                                                        <a href="post.php?id=<?php echo $comment['post_id']; ?>#comment-<?php echo $comment['id']; ?>" 
                                                           class="btn btn-sm btn-info" target="_blank">
                                                            <i class="fas fa-external-link-alt"></i> View
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <!-- SimpleMDE Editor -->
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <script>
        // Initialize SimpleMDE editor
        const easyMDE = new EasyMDE({
            element: document.getElementById('content'),
            spellChecker: false,
            autosave: {
                enabled: true,
                uniqueId: 'blog-content',
                delay: 1000,
            },
            hideIcons: ['guide', 'fullscreen', 'side-by-side'],
            showIcons: ['code', 'table'],
            placeholder: 'Write your blog post content here...',
            status: false,
            toolbar: [
                'bold', 'italic', 'heading', '|',
                'quote', 'unordered-list', 'ordered-list', '|',
                'link', 'image', '|',
                'preview', 'guide'
            ]
        });

        // Bulk selection functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const selectAllHeader = document.getElementById('selectAllHeader');
            const commentCheckboxes = document.querySelectorAll('.comment-checkbox');
            const selectedCount = document.getElementById('selectedCount');
            const applyBulkAction = document.getElementById('applyBulkAction');
            const bulkAction = document.getElementById('bulkAction');

            function updateSelection() {
                const checkedBoxes = document.querySelectorAll('.comment-checkbox:checked');
                selectedCount.textContent = `${checkedBoxes.length} selected`;
                applyBulkAction.disabled = checkedBoxes.length === 0 || bulkAction.value === '';
                
                // Update select all checkboxes
                const totalCheckboxes = commentCheckboxes.length;
                const allChecked = checkedBoxes.length === totalCheckboxes && totalCheckboxes > 0;
                selectAll.checked = allChecked;
                selectAllHeader.checked = allChecked;
            }

            // Select all functionality
            selectAll.addEventListener('change', function() {
                commentCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelection();
            });

            selectAllHeader.addEventListener('change', function() {
                commentCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelection();
            });

            // Individual checkbox change
            commentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelection);
            });

            // Bulk action select change
            bulkAction.addEventListener('change', updateSelection);

            // Initial update
            updateSelection();

            // Form validation for bulk actions
            document.getElementById('bulkForm').addEventListener('submit', function(e) {
                if (document.querySelectorAll('.comment-checkbox:checked').length === 0) {
                    e.preventDefault();
                    alert('Please select at least one comment to perform bulk action.');
                    return false;
                }
                
                if (bulkAction.value === '') {
                    e.preventDefault();
                    alert('Please select a bulk action.');
                    return false;
                }
                
                const action = bulkAction.value;
                const count = document.querySelectorAll('.comment-checkbox:checked').length;
                
                let confirmMessage = '';
                switch (action) {
                    case 'approve':
                        confirmMessage = `Approve ${count} comment(s)?`;
                        break;
                    case 'reject':
                        confirmMessage = `Reject ${count} comment(s) as spam?`;
                        break;
                    case 'delete':
                        confirmMessage = `Delete ${count} comment(s)? This action cannot be undone.`;
                        break;
                }
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
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

            // Scroll to comments section if hash is present
            if (window.location.hash === '#comments') {
                document.getElementById('comments').scrollIntoView({ behavior: 'smooth' });
            }
        });

        // File upload preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const currentImage = document.querySelector('.current-image');
                    if (currentImage) {
                        currentImage.innerHTML = `
                            <p><strong>Selected Image:</strong></p>
                            <img src="${e.target.result}" alt="Selected image" style="max-width: 200px; border-radius: 8px;">
                        `;
                    } else {
                        const fileUploadDiv = document.querySelector('.file-upload');
                        const parentDiv = fileUploadDiv.parentNode;
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'current-image';
                        previewDiv.innerHTML = `
                            <p><strong>Selected Image:</strong></p>
                            <img src="${e.target.result}" alt="Selected image" style="max-width: 200px; border-radius: 8px;">
                        `;
                        parentDiv.appendChild(previewDiv);
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>

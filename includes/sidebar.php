
<div class="sidebar">
    <div class="logo">
        <i class="fas fa-rocket logo-icon"></i>
        <div>
            <a href="index.php" class="logo-text">TechBlog Pro</a>
            <div class="logo-subtitle">Admin Dashboard</div>
        </div>
    </div>
    
    <div class="nav-section">
        <div class="nav-title">Main</div>
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
            <a href="#post-form" class="nav-item">
                <i class="fas fa-edit"></i>
                Create New
            </a>
        </nav>
    </div>
    
    <div class="nav-section">
        <div class="nav-title">Content</div>
        <nav class="nav-links">
            <a href="#" class="nav-item">
                <i class="fas fa-comments"></i>
                Comments
                <span class="badge"><?php echo $total_comments; ?></span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-tags"></i>
                Categories
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-users"></i>
                Users
            </a>
        </nav>
    </div>
    
    <div class="user-info">
        <div class="user-avatar">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="user-details">
            <h4><?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></h4>
            <p>Administrator</p>
        </div>
        <div class="user-stats">
            <div class="user-stat">
                <span class="number"><?php echo $total_posts; ?></span>
                <span class="label">Posts</span>
            </div>
            <div class="user-stat">
                <span class="number"><?php echo $total_comments; ?></span>
                <span class="label">Comments</span>
            </div>
        </div>
    </div>
</div>


<div class="dashboard-grid">
    <div class="stat-card fade-in" style="animation-delay: 0.1s">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="fas fa-newspaper"></i>
            </div>
            <div class="trend">
                <i class="fas fa-arrow-up"></i> 12%
            </div>
        </div>
        <div class="stat-number"><?php echo $total_posts; ?></div>
        <div class="stat-label">Total Posts</div>
    </div>

    <div class="stat-card success fade-in" style="animation-delay: 0.2s">
        <div class="stat-header">
            <div class="stat-icon success">
                <i class="fas fa-eye"></i>
            </div>
            <div class="trend">
                <i class="fas fa-arrow-up"></i> 25%
            </div>
        </div>
        <div class="stat-number"><?php echo number_format($total_views); ?></div>
        <div class="stat-label">Total Views</div>
    </div>

    <div class="stat-card warning fade-in" style="animation-delay: 0.3s">
        <div class="stat-header">
            <div class="stat-icon warning">
                <i class="fas fa-comments"></i>
            </div>
            <div class="trend down">
                <i class="fas fa-arrow-down"></i> 5%
            </div>
        </div>
        <div class="stat-number"><?php echo $total_comments; ?></div>
        <div class="stat-label">Comments</div>
    </div>

    <div class="stat-card info fade-in" style="animation-delay: 0.4s">
        <div class="stat-header">
            <div class="stat-icon info">
                <i class="fas fa-users"></i>
            </div>
            <div class="trend">
                <i class="fas fa-arrow-up"></i> 8%
            </div>
        </div>
        <div class="stat-number"><?php echo $total_authors; ?></div>
        <div class="stat-label">Authors</div>
    </div>
</div>

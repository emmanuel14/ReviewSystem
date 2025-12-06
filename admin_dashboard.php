<?php
require_once 'config.php';
require_admin();

$conn = getDBConnection();

// Get statistics
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
$avg_rating = $conn->query("SELECT AVG(rating) as avg FROM reviews")->fetch_assoc()['avg'];
$pending_actions = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE visible_to_workers = 'No'")->fetch_assoc()['count'];
$new_today = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];

// Get recent reviews
$recent_reviews = $conn->query("SELECT * FROM reviews ORDER BY created_at DESC LIMIT 7")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Church Workers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #f5f7fa;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #2c3e50;
            color: white;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 24px 20px;
            background: #34495e;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-icon {
            width: 40px;
            height: 40px;
            background: #3b5998;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .sidebar-title h3 {
            font-size: 16px;
            font-weight: 600;
        }
        
        .sidebar-title p {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .sidebar-section {
            padding: 12px 0;
        }
        
        .sidebar-label {
            padding: 12px 20px;
            font-size: 11px;
            text-transform: uppercase;
            opacity: 0.5;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .sidebar-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: background 0.2s;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        
        .sidebar-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-item.active {
            background: rgba(52, 152, 219, 0.2);
            border-left: 3px solid #3498db;
        }
        
        .sidebar-item-icon {
            width: 20px;
            font-size: 16px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
        }
        
        .user-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .user-details p {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .logout-btn:hover {
            background: #f8f9fa;
            border-color: #cbd5e0;
        }
        
        /* Content Area */
        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3a5f 100%);
            padding: 48px 32px;
            color: white;
        }
        
        .hero-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .hero-icon {
            font-size: 48px;
        }
        
        .hero-text h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .hero-text p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 32px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .stat-content h3 {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .stat-content .value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.blue {
            background: #e3f2fd;
        }
        
        .stat-icon.yellow {
            background: #fff9e6;
        }
        
        .stat-icon.purple {
            background: #f3e5f5;
        }
        
        .stat-icon.green {
            background: #e8f5e9;
        }
        
        /* Recent Reviews Section */
        .reviews-section {
            padding: 0 32px 32px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            font-size: 22px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .view-all {
            color: #3498db;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        .reviews-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .reviews-toolbar {
            padding: 20px 24px;
            border-bottom: 1px solid #e1e8ed;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        
        .search-box {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-box::before {
            content: '🔍';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
        }
        
        .filter-group {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .filter-select {
            padding: 10px 16px;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        
        .reviews-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .reviews-table thead {
            background: #f8f9fa;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .reviews-table th {
            padding: 12px 24px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        
        .reviews-table td {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f3f5;
            font-size: 14px;
            color: #2c3e50;
        }
        
        .reviews-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .reviewer-name {
            font-weight: 600;
        }
        
        .department-badge {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 16px;
        }
        
        .review-comment {
            color: #7f8c8d;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .review-date {
            color: #95a5a6;
            font-size: 13px;
        }
        
        .reviews-footer {
            padding: 16px 24px;
            border-top: 1px solid #e1e8ed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .showing-text {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .view-all-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 6px;
            font-size: 13px;
            color: #2c3e50;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .view-all-btn:hover {
            background: #f8f9fa;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -280px;
                z-index: 100;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-icon">🏛️</div>
            <div class="sidebar-title">
                <h3>Church Workers</h3>
                <p>Performance Review System</p>
            </div>
        </div>
        
        <nav class="sidebar-section">
            <div class="sidebar-label">Menu</div>
            <a href="admin_dashboard.php" class="sidebar-item active">
                <span class="sidebar-item-icon">📊</span>
                <span>Dashboard</span>
            </a>
        </nav>
        
        <nav class="sidebar-section">
            <div class="sidebar-label">All Departments</div>
            <?php foreach (get_departments() as $dept): ?>
                <a href="department_page.php?dept=<?php echo urlencode($dept); ?>" class="sidebar-item">
                    <span class="sidebar-item-icon"><?php echo get_department_icon($dept); ?></span>
                    <span><?php echo sanitize_output($dept); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div></div>
            <div class="user-info">
                <div class="user-details">
                    <h4><?php echo sanitize_output($_SESSION['user_name']); ?></h4>
                    <p>Admin</p>
                </div>
                <div class="user-avatar">PF</div>
                <a href="logout.php" class="logout-btn">
                    <span>🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="hero-content">
                    <div class="hero-icon">📊</div>
                    <div class="hero-text">
                        <h1>Admin Dashboard</h1>
                        <p>Comprehensive overview of all reviews, performance metrics, and worker activities</p>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Total Reviews</h3>
                        <div class="value"><?php echo $total_reviews; ?></div>
                    </div>
                    <div class="stat-icon blue">📄</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Average Rating</h3>
                        <div class="value"><?php echo number_format($avg_rating, 1); ?></div>
                    </div>
                    <div class="stat-icon yellow">⭐</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>Pending Actions</h3>
                        <div class="value"><?php echo $pending_actions; ?></div>
                    </div>
                    <div class="stat-icon purple">ℹ️</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <h3>New Reviews Today</h3>
                        <div class="value"><?php echo $new_today; ?></div>
                    </div>
                    <div class="stat-icon green">⚡</div>
                </div>
            </div>
            
            <!-- Recent Reviews Section -->
            <div class="reviews-section">
                <div class="section-header">
                    <h2>Recent Reviews</h2>
                    <a href="admin_reviews.php" class="view-all">View All →</a>
                </div>
                
                <div class="reviews-container">
                    <div class="reviews-toolbar">
                        <div class="search-box">
                            <input type="text" placeholder="Search by reviewer name or comment...">
                        </div>
                        
                        <div class="filter-group">
                            <span style="font-size: 14px; color: #7f8c8d;">Filter by Department</span>
                            <select class="filter-select">
                                <option>All Departments</option>
                                <?php foreach (get_departments() as $dept): ?>
                                    <option><?php echo sanitize_output($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <span style="font-size: 14px; color: #7f8c8d;">Sort By</span>
                            <select class="filter-select">
                                <option>Latest</option>
                                <option>Rating</option>
                                <option>Department</option>
                            </select>
                        </div>
                    </div>
                    
                    <table class="reviews-table">
                        <thead>
                            <tr>
                                <th>Reviewer</th>
                                <th>Department</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_reviews as $review): ?>
                                <tr>
                                    <td class="reviewer-name"><?php echo sanitize_output($review['reviewer_name']); ?></td>
                                    <td><span class="department-badge"><?php echo sanitize_output($review['reviewer_department']); ?></span></td>
                                    <td class="rating-stars">
                                        <?php for($i = 0; $i < $review['rating']; $i++): ?>★<?php endfor; ?>
                                    </td>
                                    <td class="review-comment"><?php echo sanitize_output($review['comment']); ?></td>
                                    <td class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="reviews-footer">
                        <div class="showing-text">Showing <?php echo count($recent_reviews); ?> of <?php echo $total_reviews; ?> reviews</div>
                        <a href="admin_reviews.php" class="view-all-btn">
                            <span>⋯</span>
                            <span>View All Reviews</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
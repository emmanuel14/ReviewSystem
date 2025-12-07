<?php
require_once 'config.php';
require_login();

$dept_name = $_GET['dept'] ?? '';
$valid_departments = get_departments();

if (!in_array($dept_name, $valid_departments)) {
    header("Location: worker_dashboard.php");
    exit();
}

$conn = getDBConnection();

// Fetch department reviews
$stmt = $conn->prepare("SELECT reviewer_department, rating, comment, created_at FROM reviews WHERE reviewer_department = ? AND visible_to_workers = 'Yes' ORDER BY created_at DESC");
$stmt->bind_param("s", $dept_name);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE reviewer_department = ? AND visible_to_workers = 'Yes'");
$stmt->bind_param("s", $dept_name);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();

// Department descriptions and background images
$dept_info = [
    'Spirit & Life (THE WORD)' => ['desc' => 'Focuses on teaching, spiritual growth, and scriptural foundation.', 'bg' => 'linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwMCIgaGVpZ2h0PSI0MDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEyMDAiIGhlaWdodD0iNDAwIiBmaWxsPSIjNjY3ZWVhIi8+PC9zdmc+)'],
    'Spirit & Power Ministry (MUSIC)' => ['desc' => 'Leads worship, praise, and musical administration.', 'bg' => 'linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwMCIgaGVpZ2h0PSI0MDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEyMDAiIGhlaWdodD0iNDAwIiBmaWxsPSIjMzQ0OTVlIi8+PC9zdmc+)'],
];

$dept_description = isset($dept_info[$dept_name]) ? $dept_info[$dept_name]['desc'] : 'Serving with excellence in ministry.';
$dept_bg = isset($dept_info[$dept_name]) ? $dept_info[$dept_name]['bg'] : 'linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), #667eea';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize_output($dept_name); ?> - Church Workers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f7fa;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        .sidebar {
            width: 260px;
            background: #f8f9fa;
            border-right: 1px solid #e1e8ed;
            padding: 24px 0;
        }
        
        .sidebar-header {
            padding: 0 24px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-icon {
            width: 48px;
            height: 48px;
            background: #2c5aa0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .sidebar-title h3 {
            font-size: 16px;
            color: #2c3e50;
        }
        
        .sidebar-title p {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .back-arrow {
            padding: 12px 24px;
            cursor: pointer;
            font-size: 20px;
            color: #2c3e50;
        }
        
        .sidebar-menu {
            margin-top: 24px;
        }
        
        .menu-item {
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            color: #2c3e50;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .menu-item:hover {
            background: #e9ecef;
        }
        
        .menu-item.active {
            background: #e9ecef;
            font-weight: 600;
        }
        
        .menu-label {
            padding: 12px 24px;
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .top-bar {
            background: white;
            padding: 16px 32px;
            display: flex;
            justify-content: flex-end;
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
            background: #2c5aa0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
        }
        
        .user-details h4 {
            font-size: 14px;
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
            text-decoration: none;
            color: #2c3e50;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .logout-btn:hover {
            background: #f8f9fa;
        }
        
        .content-area {
            flex: 1;
            overflow-y: auto;
        }
        
        .hero-banner {
            height: 320px;
            background: <?php echo $dept_bg; ?>;
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 40px;
        }
        
        .hero-icon {
            width: 80px;
            height: 80px;
            background: rgba(251, 191, 36, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 24px;
        }
        
        .hero-banner h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .hero-banner p {
            font-size: 16px;
            opacity: 0.95;
        }
        
        .back-button {
            padding: 24px 32px;
            background: white;
        }
        
        .back-button a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2c3e50;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .back-button a:hover {
            color: #2c5aa0;
        }
        
        .stats-section {
            padding: 32px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-card.blue {
            background: #e3f2fd;
        }
        
        .stat-card.yellow {
            background: #fff9e6;
        }
        
        .stat-content h3 {
            font-size: 13px;
            color: #7f8c8d;
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
            background: rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .feedback-section {
            padding: 0 32px 32px;
        }
        
        .feedback-card {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .feedback-content h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .feedback-content p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .submit-btn {
            padding: 12px 24px;
            background: #2c5aa0;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .submit-btn:hover {
            background: #1e3a5f;
        }
        
        .reviews-section {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .reviews-header {
            margin-bottom: 24px;
        }
        
        .reviews-header h2 {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .reviews-header p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-icon {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 16px;
        }
        
        .empty-state h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        
        .empty-btn {
            padding: 12px 24px;
            background: #2c5aa0;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .review-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .review-rating {
            color: #fbbf24;
            font-size: 16px;
        }
        
        .review-date {
            color: #95a5a6;
            font-size: 13px;
        }
        
        .review-comment {
            color: #2c3e50;
            line-height: 1.6;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-icon">🏛️</div>
            <div class="sidebar-title">
                <h3>Church Workers</h3>
                <p>Performance Review System</p>
            </div>
        </div>
        
        <div class="back-arrow" onclick="history.back()">←</div>
        
        <div class="sidebar-menu">
            <a href="worker_dashboard.php" class="menu-item">
                <span>📊</span>
                <span>Dashboard</span>
            </a>
            
            <div class="menu-label">My Department</div>
            <a href="#" class="menu-item active">
                <span><?php echo get_department_icon($dept_name); ?></span>
                <span><?php echo sanitize_output($dept_name); ?></span>
            </a>
        </div>
    </aside>
    
    <main class="main-content">
        <div class="top-bar">
            <div class="user-info">
                <div class="user-details">
                    <h4><?php echo sanitize_output($_SESSION['user_name']); ?></h4>
                    <p>Worker</p>
                </div>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?></div>
                <a href="logout.php" class="logout-btn">
                    <span>🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <div class="content-area">
            <div class="hero-banner">
                <div class="hero-icon"><?php echo get_department_icon($dept_name); ?></div>
                <h1><?php echo sanitize_output($dept_name); ?></h1>
                <p><?php echo $dept_description; ?></p>
            </div>
            
            <div class="back-button">
                <a href="worker_dashboard.php">← Back to Dashboard</a>
            </div>
            
            <div class="stats-section">
                <div class="stat-card yellow">
                    <div class="stat-content">
                        <h3>Average Rating</h3>
                        <div class="value"><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></div>
                    </div>
                    <div class="stat-icon">⭐</div>
                </div>
                
                <div class="stat-card blue">
                    <div class="stat-content">
                        <h3>Total Reviews</h3>
                        <div class="value"><?php echo $stats['total_reviews'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon">💬</div>
                </div>
            </div>
            
            <div class="feedback-section">
                <div class="feedback-card">
                    <div class="feedback-content">
                        <h2>Share Your Feedback</h2>
                        <p>Help us improve by submitting a review for this department</p>
                    </div>
                    <a href="worker_dashboard.php#submit-review" class="submit-btn">
                        <span>+</span>
                        <span>Submit Review</span>
                    </a>
                </div>
                
                <div class="reviews-section">
                    <div class="reviews-header">
                        <h2>Recent Reviews</h2>
                        <p><?php echo count($reviews) > 0 ? 'Feedback from colleagues' : 'No reviews yet. Be the first to share feedback!'; ?></p>
                    </div>
                    
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-rating">
                                        <?php for($i = 0; $i < $review['rating']; $i++): ?>★<?php endfor; ?>
                                    </div>
                                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-comment"><?php echo nl2br(sanitize_output($review['comment'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📝</div>
                            <h3>No Reviews Yet</h3>
                            <p>Be the first to share feedback about this department</p>
                            <a href="worker_dashboard.php#submit-review" class="empty-btn">
                                <span>+</span>
                                <span>Submit First Review</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
<?php
require_once 'config.php';
require_admin();

$conn = getDBConnection();

// Time period filter
$filter_period = $_GET['period'] ?? 'month';
$date_condition = "";
$period_label = "This Month";

switch($filter_period) {
    case 'today':
        $date_condition = "WHERE DATE(created_at) = CURDATE()";
        $period_label = "Today";
        break;
    case 'week':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $period_label = "This Week";
        break;
    case 'month':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $period_label = "This Month";
        break;
    case 'quarter':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        $period_label = "This Quarter";
        break;
    case 'year':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $period_label = "This Year";
        break;
    default:
        $date_condition = "";
        $period_label = "All Time";
}

// Overall statistics
$overall_query = "SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    COUNT(DISTINCT reviewer_department) as departments_reviewed,
    COUNT(DISTINCT reviewer_name) as total_reviewers,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_count,
    SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as high_ratings,
    MAX(created_at) as last_review_date
    FROM reviews $date_condition";
$overall_stats = $conn->query($overall_query)->fetch_assoc();

// Prevent division by zero
if ($overall_stats['total_reviews'] == 0) {
    $overall_stats['avg_rating'] = 0;
    $overall_stats['high_ratings'] = 0;
}

// Calculate improvement
$prev_interval = $filter_period == 'week' ? '1 WEEK' : ($filter_period == 'month' ? '1 MONTH' : '3 MONTH');
$prev_span = $filter_period == 'week' ? '2 WEEK' : ($filter_period == 'month' ? '2 MONTH' : '6 MONTH');

$prev_period_query = "SELECT AVG(rating) as prev_avg FROM reviews 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL $prev_interval) 
    AND created_at >= DATE_SUB(NOW(), INTERVAL $prev_span)";
$prev_stats = $conn->query($prev_period_query)->fetch_assoc();
$improvement = ($prev_stats['prev_avg'] > 0) ? 
    (($overall_stats['avg_rating'] - $prev_stats['prev_avg']) / $prev_stats['prev_avg'] * 100) : 0;

// Satisfaction score
$satisfaction_score = $overall_stats['total_reviews'] > 0 ? 
    ($overall_stats['high_ratings'] / $overall_stats['total_reviews'] * 100) : 0;

// Department statistics
$dept_stats_query = "SELECT 
    reviewer_department,
    COUNT(*) as review_count,
    AVG(rating) as avg_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM reviews 
    $date_condition
    GROUP BY reviewer_department
    ORDER BY avg_rating DESC, review_count DESC";
$dept_stats_result = $conn->query($dept_stats_query);
$dept_stats = $dept_stats_result ? $dept_stats_result->fetch_all(MYSQLI_ASSOC) : [];

// Time series data (last 30 days)
$time_series_query = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as review_count,
    AVG(rating) as avg_rating
    FROM reviews
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
$time_series = $conn->query($time_series_query)->fetch_all(MYSQLI_ASSOC);

// Rating distribution
$rating_dist = array_fill(1, 5, 0);
foreach ($dept_stats as $dept) {
    $rating_dist[5] += $dept['five_star'];
    $rating_dist[4] += $dept['four_star'];
    $rating_dist[3] += $dept['three_star'];
    $rating_dist[2] += $dept['two_star'];
    $rating_dist[1] += $dept['one_star'];
}

// Top reviewers
$top_reviewers_query = "SELECT 
    reviewer_name, 
    COUNT(*) as review_count,
    AVG(rating) as avg_rating
    FROM reviews 
    $date_condition
    GROUP BY reviewer_name
    ORDER BY review_count DESC
    LIMIT 5";
$top_reviewers_result = $conn->query($top_reviewers_query);
$top_reviewers = $top_reviewers_result ? $top_reviewers_result->fetch_all(MYSQLI_ASSOC) : [];

// Recent reviews
$recent_reviews_query = "SELECT 
    reviewer_name, 
    reviewer_department, 
    rating, 
    comment, 
    created_at 
    FROM reviews 
    WHERE comment != '' 
    " . ($date_condition ? "AND " . substr($date_condition, 6) : "") . "
    ORDER BY created_at DESC 
    LIMIT 8";
$recent_reviews_result = $conn->query($recent_reviews_query);
$recent_reviews = $recent_reviews_result ? $recent_reviews_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Professional</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 24px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
        }
        
        .sidebar-header {
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 32px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        
        .user-info p {
            font-size: 13px;
            opacity: 0.7;
        }
        
        .nav-menu {
            margin-bottom: 32px;
        }
        
        .nav-section {
            margin-bottom: 24px;
        }
        
        .nav-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.5;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: white;
            margin-bottom: 4px;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-item.active {
            background: rgba(59, 130, 246, 0.2);
            border-left: 3px solid #3b82f6;
        }
        
        .nav-icon {
            font-size: 18px;
            width: 20px;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 32px;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .page-title {
            flex: 1;
        }
        
        .page-title h1 {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        
        .page-title p {
            color: #64748b;
            font-size: 14px;
        }
        
        .top-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .filter-dropdown {
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            color: #475569;
        }
        
        .filter-dropdown:hover {
            border-color: #3b82f6;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        
        .btn-outline {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        
        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .kpi-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #f1f5f9;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .kpi-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }
        
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .kpi-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .kpi-value {
            font-size: 36px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .kpi-change {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .kpi-change.positive {
            background: #dcfce7;
            color: #15803d;
        }
        
        .kpi-change.negative {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .kpi-change.neutral {
            background: #f1f5f9;
            color: #475569;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .chart-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #f1f5f9;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
        }
        
        .chart-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .dept-rankings {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #f1f5f9;
            margin-bottom: 32px;
        }
        
        .dept-item {
            display: flex;
            align-items: center;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            background: #f8fafc;
            transition: all 0.2s;
        }
        
        .dept-item:hover {
            background: #f1f5f9;
            transform: translateX(4px);
        }
        
        .dept-rank {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 16px;
        }
        
        .dept-info {
            flex: 1;
        }
        
        .dept-name {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }
        
        .dept-stats {
            font-size: 13px;
            color: #64748b;
        }
        
        .dept-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 700;
            color: #3b82f6;
        }
        
        .dept-stars {
            color: #fbbf24;
            font-size: 14px;
        }
        
        .activity-feed {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #f1f5f9;
        }
        
        .activity-item {
            padding: 16px;
            border-left: 3px solid #e2e8f0;
            margin-bottom: 16px;
            border-radius: 8px;
            background: #f8fafc;
            transition: all 0.2s;
        }
        
        .activity-item:hover {
            background: #f1f5f9;
            border-left-color: #3b82f6;
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .activity-user {
            font-weight: 600;
            color: #0f172a;
        }
        
        .activity-dept {
            font-size: 13px;
            color: #3b82f6;
            font-weight: 500;
        }
        
        .activity-time {
            font-size: 12px;
            color: #94a3b8;
        }
        
        .activity-rating {
            color: #fbbf24;
            margin-right: 8px;
        }
        
        .activity-comment {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .kpi-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">⛪</div>
                    <span>Church Analytics</span>
                </div>
                <div class="sidebar-user">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
                    <div class="user-info">
                        <div style="font-weight: 600; font-size: 14px;"><?php echo sanitize_output($_SESSION['user_name']); ?></div>
                        <p>Administrator</p>
                    </div>
                </div>
            </div>
            
            <nav class="nav-menu">
                <div class="nav-section">
                    <div class="nav-label">Main Menu</div>
                    <a href="admin_dashboard.php" class="nav-item">
                        <span class="nav-icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="admin_analytics.php" class="nav-item active">
                        <span class="nav-icon">📈</span>
                        <span>Analytics</span>
                    </a>
                    <a href="admin_reviews.php" class="nav-item">
                        <span class="nav-icon">💬</span>
                        <span>Reviews</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-label">Account</div>
                    <a href="logout.php" class="nav-item">
                        <span class="nav-icon">🚪</span>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Performance Analytics</h1>
                    <p>Comprehensive insights • <?php echo $period_label; ?></p>
                </div>
                <div class="top-actions">
                    <form method="GET" style="display: inline;">
                        <select name="period" class="filter-dropdown" onchange="this.form.submit()">
                            <option value="today" <?php echo $filter_period == 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $filter_period == 'week' ? 'selected' : ''; ?>>This Week</option>
                            <option value="month" <?php echo $filter_period == 'month' ? 'selected' : ''; ?>>This Month</option>
                            <option value="quarter" <?php echo $filter_period == 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
                            <option value="year" <?php echo $filter_period == 'year' ? 'selected' : ''; ?>>This Year</option>
                            <option value="all" <?php echo $filter_period == 'all' ? 'selected' : ''; ?>>All Time</option>
                        </select>
                    </form>
                    <button class="btn btn-outline" onclick="window.print()">📄 Export PDF</button>
                    <button class="btn btn-primary">📊 Generate Report</button>
                </div>
            </div>
            
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div>
                            <div class="kpi-label">Average Rating</div>
                            <div class="kpi-value"><?php echo number_format($overall_stats['avg_rating'], 2); ?></div>
                            <span class="kpi-change <?php echo $improvement >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $improvement >= 0 ? '↑' : '↓'; ?> <?php echo abs(number_format($improvement, 1)); ?>%
                            </span>
                        </div>
                        <div class="kpi-icon" style="background: linear-gradient(135deg, #fbbf24 20%, #f59e0b 100%);">⭐</div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div>
                            <div class="kpi-label">Total Reviews</div>
                            <div class="kpi-value"><?php echo number_format($overall_stats['total_reviews']); ?></div>
                            <span class="kpi-change neutral"><?php echo $overall_stats['total_reviewers']; ?> reviewers</span>
                        </div>
                        <div class="kpi-icon" style="background: linear-gradient(135deg, #3b82f6 20%, #2563eb 100%);">💬</div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div>
                            <div class="kpi-label">Satisfaction Score</div>
                            <div class="kpi-value"><?php echo number_format($satisfaction_score, 0); ?>%</div>
                            <span class="kpi-change positive">High ratings</span>
                        </div>
                        <div class="kpi-icon" style="background: linear-gradient(135deg, #10b981 20%, #059669 100%);">📊</div>
                    </div>
                </div>
                
                <div class="kpi-card">
                    <div class="kpi-header">
                        <div>
                            <div class="kpi-label">Active Departments</div>
                            <div class="kpi-value"><?php echo $overall_stats['departments_reviewed']; ?></div>
                            <span class="kpi-change neutral">of <?php echo count(get_departments()); ?> total</span>
                        </div>
                        <div class="kpi-icon" style="background: linear-gradient(135deg, #8b5cf6 20%, #7c3aed 100%);">🏢</div>
                    </div>
                </div>
            </div>
            
            <div class="chart-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <div class="chart-title">Performance Trend</div>
                            <div class="chart-subtitle">Daily ratings over the last 30 days</div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <div class="chart-title">Rating Distribution</div>
                            <div class="chart-subtitle">Breakdown of all ratings</div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="content-grid">
                <div class="dept-rankings">
                    <div class="chart-header">
                        <div>
                            <div class="chart-title">Department Rankings</div>
                            <div class="chart-subtitle">Top performing departments</div>
                        </div>
                    </div>
                    
                    <?php 
                    $rank = 1;
                    $top_depts = array_slice($dept_stats, 0, 10);
                    foreach ($top_depts as $dept): 
                    ?>
                        <div class="dept-item">
                            <div class="dept-rank"><?php echo $rank++; ?></div>
                            <div class="dept-info">
                                <div class="dept-name">
                                    <?php echo get_department_icon($dept['reviewer_department']); ?>
                                    <?php echo sanitize_output($dept['reviewer_department']); ?>
                                </div>
                                <div class="dept-stats"><?php echo $dept['review_count']; ?> reviews • <?php echo $dept['five_star']; ?> five-star</div>
                            </div>
                            <div class="dept-rating">
                                <?php echo number_format($dept['avg_rating'], 2); ?>
                                <span class="dept-stars">★</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="activity-feed">
                    <div class="chart-header">
                        <div>
                            <div class="chart-title">Recent Activity</div>
                            <div class="chart-subtitle">Latest reviews</div>
                        </div>
                    </div>
                    
                    <div style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($recent_reviews as $review): ?>
                            <div class="activity-item">
                                <div class="activity-header">
                                    <div>
                                        <div class="activity-user"><?php echo sanitize_output($review['reviewer_name']); ?></div>
                                        <div class="activity-dept"><?php echo sanitize_output($review['reviewer_department']); ?></div>
                                    </div>
                                    <div class="activity-time"><?php echo date('M d, H:i', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div>
                                    <span class="activity-rating">
                                        <?php for($i = 0; $i < $review['rating']; $i++): ?>★<?php endfor; ?>
                                    </span>
                                </div>
                                <div class="activity-comment"><?php echo sanitize_output(substr($review['comment'], 0, 120)); ?><?php echo strlen($review['comment']) > 120 ? '...' : ''; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <div>
                        <div class="chart-title">Top Contributors</div>
                        <div class="chart-subtitle">Most active reviewers</div>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f5f9;">
                                <th style="text-align: left; padding: 12px; font-size: 13px; color: #64748b; font-weight: 600;">Reviewer</th>
                                <th style="text-align: center; padding: 12px; font-size: 13px; color: #64748b; font-weight: 600;">Reviews</th>
                                <th style="text-align: center; padding: 12px; font-size: 13px; color: #64748b; font-weight: 600;">Avg Rating</th>
                                <th style="text-align: right; padding: 12px; font-size: 13px; color: #64748b; font-weight: 600;">Contribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_reviewers as $reviewer): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 16px; font-weight: 600; color: #0f172a;"><?php echo sanitize_output($reviewer['reviewer_name']); ?></td>
                                    <td style="padding: 16px; text-align: center; font-weight: 600; color: #3b82f6;"><?php echo $reviewer['review_count']; ?></td>
                                    <td style="padding: 16px; text-align: center; color: #fbbf24; font-weight: 600;"><?php echo number_format($reviewer['avg_rating'], 2); ?> ★</td>
                                    <td style="padding: 16px; text-align: right;">
                                        <div style="background: linear-gradient(90deg, #3b82f6 0%, #3b82f6 <?php echo $overall_stats['total_reviews'] > 0 ? ($reviewer['review_count'] / $overall_stats['total_reviews'] * 100) : 0; ?>%, #f1f5f9 <?php echo $overall_stats['total_reviews'] > 0 ? ($reviewer['review_count'] / $overall_stats['total_reviews'] * 100) : 0; ?>%); height: 8px; border-radius: 4px; min-width: 100px;"></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendData = <?php echo json_encode($time_series); ?>;
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Average Rating',
                    data: trendData.map(d => parseFloat(d.avg_rating)),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return 'Rating: ' + context.parsed.y.toFixed(2) + ' ★';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: { stepSize: 1, color: '#64748b' },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { color: '#64748b' },
                        grid: { display: false }
                    }
                }
            }
        });
        
        const distCtx = document.getElementById('distributionChart').getContext('2d');
        const ratingDist = <?php echo json_encode(array_values($rating_dist)); ?>;
        
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    data: ratingDist,
                    backgroundColor: ['#ef4444', '#f97316', '#eab308', '#3b82f6', '#10b981'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, font: { size: 13 }, usePointStyle: true }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
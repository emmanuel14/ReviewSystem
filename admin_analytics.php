<?php
require_once 'config.php';
require_admin();

$conn = getDBConnection();

// Overall statistics
$overall_query = "SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    COUNT(DISTINCT reviewer_department) as departments_reviewed,
    COUNT(DISTINCT reviewer_name) as total_reviewers
    FROM reviews";
$overall_stats = $conn->query($overall_query)->fetch_assoc();

// Department statistics with rankings
$dept_stats_query = "SELECT 
    reviewer_department,
    COUNT(*) as review_count,
    AVG(rating) as avg_rating,
    MAX(rating) as highest_rating,
    MIN(rating) as lowest_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM reviews
    GROUP BY reviewer_department
    ORDER BY avg_rating DESC, review_count DESC";
$dept_stats = $conn->query($dept_stats_query)->fetch_all(MYSQLI_ASSOC);

// Rating distribution
$rating_dist_query = "SELECT 
    rating, 
    COUNT(*) as count 
    FROM reviews 
    GROUP BY rating 
    ORDER BY rating DESC";
$rating_dist_result = $conn->query($rating_dist_query);
$rating_distribution = array_fill(1, 5, 0);
while ($row = $rating_dist_result->fetch_assoc()) {
    $rating_distribution[$row['rating']] = $row['count'];
}

// Monthly trends (last 6 months)
$monthly_query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as review_count,
    AVG(rating) as avg_rating
    FROM reviews
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC";
$monthly_trends = $conn->query($monthly_query)->fetch_all(MYSQLI_ASSOC);

// Top performers (highest rated departments)
$top_performers = array_slice($dept_stats, 0, 5);

// Needs improvement (lowest rated departments)
$needs_improvement = array_reverse(array_slice($dept_stats, -5));

// Recent activity
$recent_activity_query = "SELECT 
    reviewer_name,
    reviewer_department,
    rating,
    created_at
    FROM reviews
    ORDER BY created_at DESC
    LIMIT 10";
$recent_activity = $conn->query($recent_activity_query)->fetch_all(MYSQLI_ASSOC);

// Worker participation
$worker_participation_query = "SELECT 
    reviewer_name,
    COUNT(*) as review_count
    FROM reviews
    GROUP BY reviewer_name
    ORDER BY review_count DESC
    LIMIT 10";
$worker_participation = $conn->query($worker_participation_query)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .sidebar-header .icon {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .sidebar-header .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #28a745;
            border-radius: 12px;
            font-size: 12px;
            margin-top: 8px;
        }
        
        .menu-section {
            margin-bottom: 30px;
        }
        
        .menu-section h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            opacity: 0.7;
        }
        
        .menu-item {
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .menu-item .icon {
            font-size: 20px;
        }
        
        .logout-btn {
            margin-top: 30px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: background 0.2s;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .top-bar h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .top-bar p {
            color: #666;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .stat-card .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }
        
        .stat-card .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-icon.blue { background: #e3f2fd; }
        .stat-icon.green { background: #e8f5e9; }
        .stat-icon.purple { background: #f3e5f5; }
        .stat-icon.orange { background: #fff3e0; }
        
        .stat-card .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            margin: 20px 0;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .rating-label {
            min-width: 80px;
            color: #ffc107;
            font-size: 16px;
            font-weight: 600;
        }
        
        .bar-container {
            flex: 1;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-size: 12px;
            font-weight: 600;
            transition: width 0.8s ease;
        }
        
        .dept-ranking {
            display: grid;
            gap: 15px;
        }
        
        .dept-rank-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .rank-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        
        .rank-info {
            flex: 1;
        }
        
        .rank-info h4 {
            color: #333;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .rank-info p {
            color: #666;
            font-size: 12px;
        }
        
        .rank-rating {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 13px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        
        .trend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .trend-month {
            font-weight: 600;
            color: #333;
        }
        
        .trend-stats {
            display: flex;
            gap: 20px;
            font-size: 13px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid, .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="icon">👨‍💼</div>
            <h2><?php echo sanitize_output($_SESSION['user_name']); ?></h2>
            <div class="badge">Administrator</div>
        </div>
        
        <div class="menu-section">
            <h3>Menu</h3>
            <a href="admin_dashboard.php" class="menu-item">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
            <a href="admin_reviews.php" class="menu-item">
                <span class="icon">💬</span>
                <span>All Reviews</span>
            </a>
            <a href="admin_analytics.php" class="menu-item active">
                <span class="icon">📈</span>
                <span>Analytics</span>
            </a>
        </div>
        
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>Performance Analytics</h1>
            <p>Comprehensive insights into department performance and engagement</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Total Reviews</h3>
                    <div class="stat-icon blue">💬</div>
                </div>
                <div class="stat-value"><?php echo number_format($overall_stats['total_reviews']); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Average Rating</h3>
                    <div class="stat-icon green">⭐</div>
                </div>
                <div class="stat-value"><?php echo number_format($overall_stats['avg_rating'], 2); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Departments Reviewed</h3>
                    <div class="stat-icon purple">🏢</div>
                </div>
                <div class="stat-value"><?php echo $overall_stats['departments_reviewed']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <h3>Active Reviewers</h3>
                    <div class="stat-icon orange">👥</div>
                </div>
                <div class="stat-value"><?php echo $overall_stats['total_reviewers']; ?></div>
            </div>
        </div>
        
        <div class="section">
            <h2>📊 Rating Distribution</h2>
            <div class="chart-container">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <div class="rating-bar">
                        <div class="rating-label"><?php echo $i; ?> <?php for ($j = 0; $j < $i; $j++) echo '★'; ?></div>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: <?php echo $overall_stats['total_reviews'] > 0 ? ($rating_distribution[$i] / $overall_stats['total_reviews'] * 100) : 0; ?>%;">
                                <?php echo $rating_distribution[$i]; ?> reviews
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="section">
                <h2>🏆 Top Performing Departments</h2>
                <div class="dept-ranking">
                    <?php $rank = 1; ?>
                    <?php foreach ($top_performers as $dept): ?>
                        <div class="dept-rank-item">
                            <div class="rank-number"><?php echo $rank++; ?></div>
                            <div class="rank-info">
                                <h4><?php echo sanitize_output($dept['reviewer_department']); ?></h4>
                                <p><?php echo $dept['review_count']; ?> reviews</p>
                            </div>
                            <div class="rank-rating">
                                <?php echo number_format($dept['avg_rating'], 2); ?> ⭐
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="section">
                <h2>📉 Needs Improvement</h2>
                <div class="dept-ranking">
                    <?php foreach ($needs_improvement as $dept): ?>
                        <div class="dept-rank-item" style="border-left-color: #dc3545;">
                            <div class="rank-number" style="background: #dc3545;">⚠️</div>
                            <div class="rank-info">
                                <h4><?php echo sanitize_output($dept['reviewer_department']); ?></h4>
                                <p><?php echo $dept['review_count']; ?> reviews</p>
                            </div>
                            <div class="rank-rating" style="color: #dc3545;">
                                <?php echo number_format($dept['avg_rating'], 2); ?> ⭐
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>📈 Monthly Trends</h2>
            <?php if (count($monthly_trends) > 0): ?>
                <?php foreach ($monthly_trends as $trend): ?>
                    <div class="trend-item">
                        <div class="trend-month">
                            <?php echo date('F Y', strtotime($trend['month'] . '-01')); ?>
                        </div>
                        <div class="trend-stats">
                            <span><strong><?php echo $trend['review_count']; ?></strong> reviews</span>
                            <span>⭐ <strong><?php echo number_format($trend['avg_rating'], 2); ?></strong> avg</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 20px;">No trend data available yet.</p>
            <?php endif; ?>
        </div>
        
        <div class="grid-2">
            <div class="section">
                <h2>👥 Top Contributors</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Reviewer</th>
                            <th>Reviews Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($worker_participation as $worker): ?>
                            <tr>
                                <td><strong><?php echo sanitize_output($worker['reviewer_name']); ?></strong></td>
                                <td><?php echo $worker['review_count']; ?> reviews</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="section">
                <h2>🕐 Recent Activity</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Reviewer</th>
                            <th>Department</th>
                            <th>Rating</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td><?php echo sanitize_output($activity['reviewer_name']); ?></td>
                                <td><?php echo sanitize_output($activity['reviewer_department']); ?></td>
                                <td style="color: #ffc107;">
                                    <?php for ($i = 0; $i < $activity['rating']; $i++) echo '★'; ?>
                                </td>
                                <td><?php echo date('M d', strtotime($activity['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="section">
            <h2>📋 Complete Department Rankings</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Department</th>
                        <th>Avg Rating</th>
                        <th>Total Reviews</th>
                        <th>5★</th>
                        <th>4★</th>
                        <th>3★</th>
                        <th>2★</th>
                        <th>1★</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; ?>
                    <?php foreach ($dept_stats as $dept): ?>
                        <tr>
                            <td><strong><?php echo $rank++; ?></strong></td>
                            <td><?php echo sanitize_output($dept['reviewer_department']); ?></td>
                            <td><strong style="color: #667eea;"><?php echo number_format($dept['avg_rating'], 2); ?></strong></td>
                            <td><?php echo $dept['review_count']; ?></td>
                            <td><?php echo $dept['five_star']; ?></td>
                            <td><?php echo $dept['four_star']; ?></td>
                            <td><?php echo $dept['three_star']; ?></td>
                            <td><?php echo $dept['two_star']; ?></td>
                            <td><?php echo $dept['one_star']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
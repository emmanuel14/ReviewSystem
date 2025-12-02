<?php
require_once 'config.php';
require_admin();

$conn = getDBConnection();

// Time period filter
$filter_period = $_GET['period'] ?? 'month';
$date_condition = "";
switch($filter_period) {
    case 'week':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        break;
    case 'month':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case 'quarter':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        break;
    case 'year':
        $date_condition = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    default:
        $date_condition = "";
}

// Overall statistics
$overall_query = "SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    COUNT(DISTINCT reviewer_department) as departments_reviewed,
    COUNT(DISTINCT reviewer_name) as total_reviewers,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star_count,
    SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as high_ratings
    FROM reviews $date_condition";
$overall_stats = $conn->query($overall_query)->fetch_assoc();

// Calculate improvement percentage (comparing with previous period)
$prev_period_query = "SELECT AVG(rating) as prev_avg FROM reviews WHERE created_at < DATE_SUB(NOW(), INTERVAL " . 
    ($filter_period == 'week' ? '1 WEEK' : ($filter_period == 'month' ? '1 MONTH' : '3 MONTH')) . 
    ") AND created_at >= DATE_SUB(NOW(), INTERVAL " . 
    ($filter_period == 'week' ? '2 WEEK' : ($filter_period == 'month' ? '2 MONTH' : '6 MONTH')) . ")";
$prev_stats = $conn->query($prev_period_query)->fetch_assoc();
$improvement = $prev_stats['prev_avg'] > 0 ? 
    (($overall_stats['avg_rating'] - $prev_stats['prev_avg']) / $prev_stats['prev_avg'] * 100) : 0;

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
    FROM reviews $date_condition
    GROUP BY reviewer_department
    ORDER BY avg_rating DESC";
$dept_stats = $conn->query($dept_stats_query)->fetch_all(MYSQLI_ASSOC);

// Weekly trends (last 8 weeks)
$weekly_query = "SELECT 
    WEEK(created_at) as week_num,
    DATE_FORMAT(created_at, '%b %d') as week_label,
    COUNT(*) as review_count,
    AVG(rating) as avg_rating
    FROM reviews
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
    GROUP BY WEEK(created_at), DATE_FORMAT(created_at, '%b %d')
    ORDER BY WEEK(created_at) ASC";
$weekly_trends = $conn->query($weekly_query)->fetch_all(MYSQLI_ASSOC);

// Recent comments
$recent_comments_condition = "";
if ($filter_period !== 'all') {
    $recent_comments_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL " . 
        ($filter_period == 'week' ? '1 WEEK' : ($filter_period == 'month' ? '1 MONTH' : ($filter_period == 'quarter' ? '3 MONTH' : '1 YEAR'))) . ")";
}

$recent_comments_query = "SELECT reviewer_name, reviewer_department, rating, comment, created_at 
    FROM reviews WHERE comment != '' $recent_comments_condition ORDER BY created_at DESC LIMIT 10";
$recent_comments = $conn->query($recent_comments_query)->fetch_all(MYSQLI_ASSOC);

// Top and bottom performers
$top_performers = array_slice($dept_stats, 0, 3);
$bottom_performers = array_reverse(array_slice($dept_stats, -3));

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .header-left p {
            color: #666;
            font-size: 14px;
        }
        
        .header-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-select {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-select:hover {
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 0 0 0 100px;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }
        
        .stat-change {
            font-size: 14px;
            margin-top: 8px;
            font-weight: 600;
        }
        
        .stat-change.positive {
            color: #10b981;
        }
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .card-title {
            font-size: 22px;
            font-weight: 700;
            color: #333;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .dept-list {
            display: grid;
            gap: 15px;
        }
        
        .dept-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        
        .dept-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .dept-rank {
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
        
        .dept-info {
            flex: 1;
        }
        
        .dept-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        
        .dept-count {
            font-size: 12px;
            color: #666;
        }
        
        .dept-rating {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
        }
        
        .comment-card {
            padding: 15px;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 12px;
            border-left: 4px solid #667eea;
            margin-bottom: 15px;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .comment-dept {
            font-weight: 600;
            color: #333;
        }
        
        .comment-rating {
            color: #fbbf24;
        }
        
        .comment-text {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 5px;
        }
        
        .comment-date {
            font-size: 12px;
            color: #999;
        }
        
        .insights-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .insights-card h3 {
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .insight-item {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
        }
        
        .insight-item:last-child {
            margin-bottom: 0;
        }
        
        .insight-title {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .insight-text {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-link">
            ← Back to Dashboard
        </a>
        
        <div class="header">
            <div class="header-left">
                <h1>📈 Analytics Dashboard</h1>
                <p>Track service performance and engagement trends</p>
            </div>
            <div class="header-right">
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <select name="period" class="filter-select" onchange="this.form.submit()">
                        <option value="week" <?php echo $filter_period == 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $filter_period == 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="quarter" <?php echo $filter_period == 'quarter' ? 'selected' : ''; ?>>This Quarter</option>
                        <option value="year" <?php echo $filter_period == 'year' ? 'selected' : ''; ?>>This Year</option>
                        <option value="all" <?php echo $filter_period == 'all' ? 'selected' : ''; ?>>All Time</option>
                    </select>
                </form>
                <button class="btn btn-primary" onclick="window.print()">
                    📄 Export PDF
                </button>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-label">Average Rating</div>
                <div class="stat-value"><?php echo number_format($overall_stats['avg_rating'], 1); ?></div>
                <div class="stat-change <?php echo $improvement >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo $improvement >= 0 ? '↑' : '↓'; ?> <?php echo abs(number_format($improvement, 1)); ?>% vs previous period
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Total Responses</div>
                <div class="stat-value"><?php echo number_format($overall_stats['total_reviews']); ?></div>
                <div class="stat-change positive">
                    <?php echo $overall_stats['total_reviewers']; ?> unique reviewers
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-label">High Ratings (4-5★)</div>
                <div class="stat-value"><?php echo $overall_stats['total_reviews'] > 0 ? number_format(($overall_stats['high_ratings'] / $overall_stats['total_reviews']) * 100, 0) : 0; ?>%</div>
                <div class="stat-change positive">
                    <?php echo $overall_stats['high_ratings']; ?> out of <?php echo $overall_stats['total_reviews']; ?> reviews
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-label">Active Departments</div>
                <div class="stat-value"><?php echo $overall_stats['departments_reviewed']; ?></div>
                <div class="stat-change positive">
                    Out of <?php echo count(get_departments()); ?> total departments
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📈 Overall Service Trend</h3>
            </div>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📊 Department Averages</h3>
                </div>
                <div class="chart-container">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🥧 Rating Distribution</h3>
                </div>
                <div class="chart-container">
                    <canvas id="ratingDistChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🏆 Top Performing Departments</h3>
                </div>
                <div class="dept-list">
                    <?php $rank = 1; ?>
                    <?php foreach ($top_performers as $dept): ?>
                        <div class="dept-item">
                            <div class="dept-rank"><?php echo $rank++; ?></div>
                            <div class="dept-info">
                                <div class="dept-name">
                                    <?php echo get_department_icon($dept['reviewer_department']); ?>
                                    <?php echo sanitize_output($dept['reviewer_department']); ?>
                                </div>
                                <div class="dept-count"><?php echo $dept['review_count']; ?> reviews</div>
                            </div>
                            <div class="dept-rating">
                                <?php echo number_format($dept['avg_rating'], 2); ?> ⭐
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">💬 Recent Comments</h3>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($recent_comments as $comment): ?>
                        <div class="comment-card">
                            <div class="comment-header">
                                <div class="comment-dept">
                                    <?php echo get_department_icon($comment['reviewer_department']); ?>
                                    <?php echo sanitize_output($comment['reviewer_department']); ?>
                                </div>
                                <div class="comment-rating">
                                    <?php for($i = 0; $i < $comment['rating']; $i++): ?>★<?php endfor; ?>
                                </div>
                            </div>
                            <div class="comment-text"><?php echo sanitize_output(substr($comment['comment'], 0, 150)); ?><?php echo strlen($comment['comment']) > 150 ? '...' : ''; ?></div>
                            <div class="comment-date">
                                <?php echo sanitize_output($comment['reviewer_name']); ?> • 
                                <?php echo date('M d, Y', strtotime($comment['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="insights-card">
            <h3>💡 AI Insights & Recommendations</h3>
            
            <?php if (count($top_performers) > 0): ?>
                <div class="insight-item">
                    <div class="insight-title">
                        🎉 <?php echo sanitize_output($top_performers[0]['reviewer_department']); ?>
                    </div>
                    <div class="insight-text">
                        Leading with an average rating of <?php echo number_format($top_performers[0]['avg_rating'], 2); ?>★! 
                        This department has received <?php echo $top_performers[0]['review_count']; ?> reviews with 
                        <?php echo $top_performers[0]['five_star']; ?> five-star ratings. Keep up the excellent work!
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (count($bottom_performers) > 0 && $bottom_performers[0]['avg_rating'] < 4): ?>
                <div class="insight-item">
                    <div class="insight-title">
                        📌 <?php echo sanitize_output($bottom_performers[0]['reviewer_department']); ?>
                    </div>
                    <div class="insight-text">
                        This department has an average rating of <?php echo number_format($bottom_performers[0]['avg_rating'], 2); ?>★. 
                        Consider reviewing recent feedback and implementing improvements to enhance service quality.
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="insight-item">
                <div class="insight-title">
                    📊 Overall Performance Trend
                </div>
                <div class="insight-text">
                    <?php if ($improvement > 0): ?>
                        Your overall rating has improved by <?php echo number_format($improvement, 1); ?>% compared to the previous period! 
                        The positive trend shows strong engagement and service quality improvements.
                    <?php else: ?>
                        Overall rating has decreased by <?php echo abs(number_format($improvement, 1)); ?>% compared to the previous period. 
                        Review feedback patterns and consider targeted improvements in key areas.
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="insight-item">
                <div class="insight-title">
                    🎯 Engagement Metrics
                </div>
                <div class="insight-text">
                    You have <?php echo $overall_stats['total_reviewers']; ?> active reviewers providing feedback. 
                    Encourage more participation by highlighting the impact of reviews on service improvements.
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($weekly_trends, 'week_label')); ?>,
                datasets: [{
                    label: 'Average Rating',
                    data: <?php echo json_encode(array_column($weekly_trends, 'avg_rating')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
        
        // Department Chart
        const deptCtx = document.getElementById('deptChart').getContext('2d');
        const deptData = <?php echo json_encode($dept_stats); ?>;
        const topDepts = deptData.slice(0, 8);
        
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: topDepts.map(d => d.reviewer_department.split(' ')[0]),
                datasets: [{
                    label: 'Average Rating',
                    data: topDepts.map(d => parseFloat(d.avg_rating)),
                    backgroundColor: [
                        '#8B5CF6', '#EC4899', '#F59E0B', '#10B981',
                        '#06B6D4', '#3B82F6', '#6366F1', '#8B5CF6'
                    ],
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Rating Distribution Chart
        const ratingCtx = document.getElementById('ratingDistChart').getContext('2d');
        const allDeptData = <?php echo json_encode($dept_stats); ?>;
        const totalFiveStar = allDeptData.reduce((sum, d) => sum + parseInt(d.five_star), 0);
        const totalFourStar = allDeptData.reduce((sum, d) => sum + parseInt(d.four_star), 0);
        const totalThreeStar = allDeptData.reduce((sum, d) => sum + parseInt(d.three_star), 0);
        const totalTwoStar = allDeptData.reduce((sum, d) => sum + parseInt(d.two_star), 0);
        const totalOneStar = allDeptData.reduce((sum, d) => sum + parseInt(d.one_star), 0);
        
        new Chart(ratingCtx, {
            type: 'doughnut',
            data: {
                labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
                datasets: [{
                    data: [totalFiveStar, totalFourStar, totalThreeStar, totalTwoStar, totalOneStar],
                    backgroundColor: [
                        '#10B981',
                        '#3B82F6',
                        '#F59E0B',
                        '#EC4899',
                        '#EF4444'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
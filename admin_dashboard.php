<?php
require_once 'config.php';
require_admin();

$conn = getDBConnection();

// Handle delete review
if (isset($_GET['delete_review'])) {
    $review_id = intval($_GET['delete_review']);
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $stmt->close();
    
    log_activity($conn, $_SESSION['user_name'], 'Delete Review', "Review ID: $review_id");
    header("Location: admin_dashboard.php?success=Review deleted successfully");
    exit();
}

// Handle search and filter
$search_dept = $_GET['search_dept'] ?? '';
$search_query = $_GET['search_query'] ?? '';

// Fetch all reviews with filters
$sql = "SELECT r.*, w.full_name as reviewer_full_name 
        FROM reviews r 
        LEFT JOIN workers w ON r.reviewer_name = w.full_name 
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($search_dept)) {
    $sql .= " AND r.reviewer_department = ?";
    $params[] = $search_dept;
    $types .= 's';
}

if (!empty($search_query)) {
    $sql .= " AND (r.comment LIKE ? OR r.reviewer_name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get overall statistics
$stats_query = "SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    COUNT(DISTINCT reviewer_department) as total_departments
    FROM reviews";
$stats_result = $conn->query($stats_query);
$overall_stats = $stats_result->fetch_assoc();

// Get department statistics
$dept_stats_query = "SELECT 
    reviewer_department,
    COUNT(*) as review_count,
    AVG(rating) as avg_rating
    FROM reviews
    GROUP BY reviewer_department
    ORDER BY avg_rating DESC";
$dept_stats_result = $conn->query($dept_stats_query);
$dept_stats = $dept_stats_result->fetch_all(MYSQLI_ASSOC);

// Get recent activity logs
$logs_query = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10";
$logs_result = $conn->query($logs_query);
$activity_logs = $logs_result->fetch_all(MYSQLI_ASSOC);

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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h1 {
            color: #333;
            font-size: 28px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
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
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }
        
        .stat-icon.blue { background: #e3f2fd; }
        .stat-icon.green { background: #e8f5e9; }
        .stat-icon.purple { background: #f3e5f5; }
        
        .stat-info h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .stat-info p {
            color: #333;
            font-size: 28px;
            font-weight: 700;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-header h2 {
            color: #333;
            font-size: 22px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .filter-bar select, .filter-bar input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            flex: 1;
            min-width: 200px;
        }
        
        .filter-bar button {
            padding: 10px 25px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 16px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-edit {
            background: #007bff;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-edit:hover {
            background: #0056b3;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .dept-performance {
            display: grid;
            gap: 15px;
        }
        
        .dept-bar {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .dept-name {
            min-width: 250px;
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }
        
        .dept-progress {
            flex: 1;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .dept-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-size: 12px;
            font-weight: 600;
            transition: width 0.5s;
        }
        
        .activity-log {
            font-size: 13px;
        }
        
        .activity-item {
            padding: 12px;
            border-left: 3px solid #667eea;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .activity-time {
            color: #999;
            font-size: 11px;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-bar {
                flex-direction: column;
            }
            
            .filter-bar select, .filter-bar input {
                width: 100%;
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
            <div class="menu-item active">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </div>
            <a href="admin_reviews.php" class="menu-item">
                <span class="icon">💬</span>
                <span>All Reviews</span>
            </a>
            <a href="admin_analytics.php" class="menu-item">
                <span class="icon">📈</span>
                <span>Analytics</span>
            </a>
        </div>
        
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>Admin Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></div>
                <div>
                    <div style="font-weight: 600;"><?php echo sanitize_output($_SESSION['user_name']); ?></div>
                    <div style="font-size: 12px; color: #666;">Administrator</div>
                </div>
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo sanitize_output($_GET['success']); ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">💬</div>
                <div class="stat-info">
                    <h3>Total Reviews</h3>
                    <p><?php echo $overall_stats['total_reviews']; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">⭐</div>
                <div class="stat-info">
                    <h3>Average Rating</h3>
                    <p><?php echo number_format($overall_stats['avg_rating'], 1); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🏢</div>
                <div class="stat-info">
                    <h3>Active Departments</h3>
                    <p><?php echo $overall_stats['total_departments']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Department Performance</h2>
            </div>
            
            <div class="dept-performance">
                <?php foreach ($dept_stats as $dept): ?>
                    <div class="dept-bar">
                        <div class="dept-name">
                            <?php echo get_department_icon($dept['reviewer_department']); ?>
                            <?php echo sanitize_output($dept['reviewer_department']); ?>
                        </div>
                        <div class="dept-progress">
                            <div class="dept-progress-bar" style="width: <?php echo ($dept['avg_rating'] / 5 * 100); ?>%;">
                                <?php echo number_format($dept['avg_rating'], 1); ?> ⭐ (<?php echo $dept['review_count']; ?> reviews)
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>All Reviews</h2>
            </div>
            
            <form method="GET" class="filter-bar">
                <select name="search_dept">
                    <option value="">All Departments</option>
                    <?php foreach (get_departments() as $dept): ?>
                        <option value="<?php echo sanitize_output($dept); ?>" <?php echo ($search_dept === $dept) ? 'selected' : ''; ?>>
                            <?php echo sanitize_output($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search_query" placeholder="Search by name or comment..." value="<?php echo sanitize_output($search_query); ?>">
                <button type="submit">🔍 Search</button>
            </form>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?php echo $review['id']; ?></td>
                                <td><?php echo sanitize_output($review['reviewer_name']); ?></td>
                                <td><?php echo sanitize_output($review['reviewer_department']); ?></td>
                                <td>
                                    <span class="rating-stars">
                                        <?php for ($i = 0; $i < $review['rating']; $i++): ?>★<?php endfor; ?>
                                        <?php for ($i = $review['rating']; $i < 5; $i++): ?>☆<?php endfor; ?>
                                    </span>
                                </td>
                                <td><?php echo substr(sanitize_output($review['comment']), 0, 50) . '...'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                <td>
                                    <a href="edit_review.php?id=<?php echo $review['id']; ?>" class="action-btn btn-edit">Edit</a>
                                    <a href="?delete_review=<?php echo $review['id']; ?>" 
                                       class="action-btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this review?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Recent Activity</h2>
            </div>
            
            <div class="activity-log">
                <?php foreach ($activity_logs as $log): ?>
                    <div class="activity-item">
                        <strong><?php echo sanitize_output($log['user_name']); ?></strong> - <?php echo sanitize_output($log['action']); ?>
                        <?php if ($log['details']): ?>
                            <br><small><?php echo sanitize_output($log['details']); ?></small>
                        <?php endif; ?>
                        <div class="activity-time"><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
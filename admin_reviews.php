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
    header("Location: admin_reviews.php?success=Review deleted successfully");
    exit();
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_reviews = $_POST['selected_reviews'] ?? [];
    
    if (!empty($selected_reviews)) {
        if ($action === 'delete') {
            $placeholders = str_repeat('?,', count($selected_reviews) - 1) . '?';
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($selected_reviews)), ...$selected_reviews);
            $stmt->execute();
            $stmt->close();
            log_activity($conn, $_SESSION['user_name'], 'Bulk Delete Reviews', count($selected_reviews) . ' reviews deleted');
            header("Location: admin_reviews.php?success=" . count($selected_reviews) . " reviews deleted");
            exit();
        } elseif ($action === 'hide') {
            $placeholders = str_repeat('?,', count($selected_reviews) - 1) . '?';
            $stmt = $conn->prepare("UPDATE reviews SET visible_to_workers = 'No' WHERE id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($selected_reviews)), ...$selected_reviews);
            $stmt->execute();
            $stmt->close();
            log_activity($conn, $_SESSION['user_name'], 'Bulk Hide Reviews', count($selected_reviews) . ' reviews hidden');
            header("Location: admin_reviews.php?success=" . count($selected_reviews) . " reviews hidden");
            exit();
        } elseif ($action === 'show') {
            $placeholders = str_repeat('?,', count($selected_reviews) - 1) . '?';
            $stmt = $conn->prepare("UPDATE reviews SET visible_to_workers = 'Yes' WHERE id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($selected_reviews)), ...$selected_reviews);
            $stmt->execute();
            $stmt->close();
            log_activity($conn, $_SESSION['user_name'], 'Bulk Show Reviews', count($selected_reviews) . ' reviews made visible');
            header("Location: admin_reviews.php?success=" . count($selected_reviews) . " reviews made visible");
            exit();
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search_dept = $_GET['search_dept'] ?? '';
$search_rating = $_GET['search_rating'] ?? '';
$search_query = $_GET['search_query'] ?? '';
$search_visibility = $_GET['search_visibility'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search_dept)) {
    $where_conditions[] = "reviewer_department = ?";
    $params[] = $search_dept;
    $types .= 's';
}

if (!empty($search_rating)) {
    $where_conditions[] = "rating = ?";
    $params[] = intval($search_rating);
    $types .= 'i';
}

if (!empty($search_query)) {
    $where_conditions[] = "(comment LIKE ? OR reviewer_name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($search_visibility)) {
    $where_conditions[] = "visible_to_workers = ?";
    $params[] = $search_visibility;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Count total reviews
$count_sql = "SELECT COUNT(*) as total FROM reviews $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_reviews = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_reviews / $per_page);

// Fetch reviews
$sql = "SELECT * FROM reviews $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Reviews - Admin</title>
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
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .filter-grid select, .filter-grid input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filter-grid button {
            padding: 10px 25px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .bulk-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .bulk-actions select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .bulk-actions button {
            padding: 10px 20px;
            background: #28a745;
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
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-visible {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-hidden {
            background: #f8d7da;
            color: #721c24;
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
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination .active {
            background: #1e3c72;
            color: white;
            border-color: #1e3c72;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
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
            <a href="admin_reviews.php" class="menu-item active">
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
            <h1>All Reviews Management</h1>
            <div style="color: #666; font-size: 14px;">
                Showing <?php echo count($reviews); ?> of <?php echo $total_reviews; ?> reviews
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo sanitize_output($_GET['success']); ?></div>
        <?php endif; ?>
        
        <div class="section">
            <h3 style="margin-bottom: 20px; color: #333;">🔍 Filter Reviews</h3>
            
            <form method="GET" class="filter-grid">
                <select name="search_dept">
                    <option value="">All Departments</option>
                    <?php foreach (get_departments() as $dept): ?>
                        <option value="<?php echo sanitize_output($dept); ?>" <?php echo ($search_dept === $dept) ? 'selected' : ''; ?>>
                            <?php echo sanitize_output($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="search_rating">
                    <option value="">All Ratings</option>
                    <option value="5" <?php echo $search_rating === '5' ? 'selected' : ''; ?>>5 Stars</option>
                    <option value="4" <?php echo $search_rating === '4' ? 'selected' : ''; ?>>4 Stars</option>
                    <option value="3" <?php echo $search_rating === '3' ? 'selected' : ''; ?>>3 Stars</option>
                    <option value="2" <?php echo $search_rating === '2' ? 'selected' : ''; ?>>2 Stars</option>
                    <option value="1" <?php echo $search_rating === '1' ? 'selected' : ''; ?>>1 Star</option>
                </select>
                
                <select name="search_visibility">
                    <option value="">All Visibility</option>
                    <option value="Yes" <?php echo $search_visibility === 'Yes' ? 'selected' : ''; ?>>Visible</option>
                    <option value="No" <?php echo $search_visibility === 'No' ? 'selected' : ''; ?>>Hidden</option>
                </select>
                
                <input type="text" name="search_query" placeholder="Search by name or comment..." value="<?php echo sanitize_output($search_query); ?>">
                
                <button type="submit">🔍 Filter</button>
            </form>
        </div>
        
        <div class="section">
            <form method="POST" id="bulkForm">
                <div class="bulk-actions">
                    <label><input type="checkbox" id="selectAll"> Select All</label>
                    <select name="bulk_action">
                        <option value="">Bulk Actions</option>
                        <option value="delete">Delete Selected</option>
                        <option value="hide">Hide from Workers</option>
                        <option value="show">Show to Workers</option>
                    </select>
                    <button type="submit" onclick="return confirm('Are you sure you want to perform this action?')">Apply</button>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="30"><input type="checkbox" id="selectAllHeader"></th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Visibility</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($reviews) > 0): ?>
                                <?php foreach ($reviews as $review): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_reviews[]" value="<?php echo $review['id']; ?>" class="review-checkbox"></td>
                                        <td><?php echo $review['id']; ?></td>
                                        <td><strong><?php echo sanitize_output($review['reviewer_name']); ?></strong></td>
                                        <td><?php echo sanitize_output($review['reviewer_department']); ?></td>
                                        <td>
                                            <span class="rating-stars">
                                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>★<?php endfor; ?>
                                                <?php for ($i = $review['rating']; $i < 5; $i++): ?>☆<?php endfor; ?>
                                            </span>
                                        </td>
                                        <td><?php echo substr(sanitize_output($review['comment']), 0, 60) . '...'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $review['visible_to_workers'] === 'Yes' ? 'badge-visible' : 'badge-hidden'; ?>">
                                                <?php echo $review['visible_to_workers'] === 'Yes' ? 'Visible' : 'Hidden'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_review.php?id=<?php echo $review['id']; ?>" class="action-btn btn-edit">Edit</a>
                                            <a href="?delete_review=<?php echo $review['id']; ?>" 
                                               class="action-btn btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this review?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; color: #999;">
                                        No reviews found matching your criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search_dept=<?php echo urlencode($search_dept); ?>&search_rating=<?php echo urlencode($search_rating); ?>&search_query=<?php echo urlencode($search_query); ?>&search_visibility=<?php echo urlencode($search_visibility); ?>">« Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search_dept=<?php echo urlencode($search_dept); ?>&search_rating=<?php echo urlencode($search_rating); ?>&search_query=<?php echo urlencode($search_query); ?>&search_visibility=<?php echo urlencode($search_visibility); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search_dept=<?php echo urlencode($search_dept); ?>&search_rating=<?php echo urlencode($search_rating); ?>&search_query=<?php echo urlencode($search_query); ?>&search_visibility=<?php echo urlencode($search_visibility); ?>">Next »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.review-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
        
        document.getElementById('selectAllHeader').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.review-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>
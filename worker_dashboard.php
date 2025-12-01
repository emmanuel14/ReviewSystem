<?php
require_once 'config.php';
require_login();

$conn = getDBConnection();
$user_department = $_SESSION['department'];
$user_name = $_SESSION['user_name'];

// Handle review submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $review_department = trim($_POST['review_department'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error_message = 'Please select a valid rating (1-5 stars).';
    } elseif (empty($comment)) {
        $error_message = 'Please provide a comment.';
    } elseif (empty($review_department)) {
        $error_message = 'Please select a department to review.';
    } else {
        $stmt = $conn->prepare("INSERT INTO reviews (reviewer_name, reviewer_department, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $user_name, $review_department, $rating, $comment);
        
        if ($stmt->execute()) {
            $success_message = 'Review submitted successfully!';
            log_activity($conn, $user_name, 'Submit Review', "Reviewed: $review_department - Rating: $rating stars");
        } else {
            $error_message = 'Failed to submit review. Please try again.';
        }
        $stmt->close();
    }
}

// Get filter department
$filter_dept = $_GET['filter_dept'] ?? '';

// Fetch reviews (all departments or filtered)
if (!empty($filter_dept)) {
    $stmt = $conn->prepare("SELECT id, reviewer_department, rating, comment, created_at FROM reviews WHERE reviewer_department = ? AND visible_to_workers = 'Yes' ORDER BY created_at DESC LIMIT 50");
    $stmt->bind_param("s", $filter_dept);
} else {
    $stmt = $conn->prepare("SELECT id, reviewer_department, rating, comment, created_at FROM reviews WHERE visible_to_workers = 'Yes' ORDER BY created_at DESC LIMIT 50");
}
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate overall statistics
$overall_stats = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE visible_to_workers = 'Yes'")->fetch_assoc();

// Calculate department statistics
$dept_stats_query = "SELECT reviewer_department, AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE visible_to_workers = 'Yes' GROUP BY reviewer_department ORDER BY avg_rating DESC";
$dept_stats_result = $conn->query($dept_stats_query);
$dept_stats = [];
while ($row = $dept_stats_result->fetch_assoc()) {
    $dept_stats[$row['reviewer_department']] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard - Church Workers</title>
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
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
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
        
        .sidebar-header p {
            font-size: 14px;
            opacity: 0.8;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .form-grid {
            display: grid;
            gap: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .star-rating {
            display: flex;
            gap: 10px;
            font-size: 30px;
        }
        
        .star {
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }
        
        .star.active, .star:hover {
            color: #ffc107;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .departments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dept-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
        }
        
        .dept-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .dept-card .dept-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .dept-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .dept-card .dept-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 13px;
        }
        
        .dept-card .rating {
            font-weight: 600;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-bar select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            flex: 1;
            min-width: 200px;
        }
        
        .filter-bar button {
            padding: 10px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .review-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .review-department {
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .review-date {
            color: #999;
            font-size: 12px;
        }
        
        .review-stars {
            color: #ffc107;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .review-comment {
            color: #555;
            line-height: 1.6;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state .icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
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
            
            .departments-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="icon"><?php echo get_department_icon($user_department); ?></div>
            <h2><?php echo sanitize_output($user_name); ?></h2>
            <p><?php echo sanitize_output($user_department); ?></p>
        </div>
        
        <div class="menu-section">
            <h3>Menu</h3>
            <div class="menu-item active">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </div>
        </div>
        
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
    
    <div class="main-content">
        <div class="top-bar">
            <h1>Worker Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <div>
                    <div style="font-weight: 600;"><?php echo sanitize_output($user_name); ?></div>
                    <div style="font-size: 12px; color: #666;">Worker</div>
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">⭐</div>
                <div class="stat-info">
                    <h3>Overall Average Rating</h3>
                    <p><?php echo number_format($overall_stats['avg_rating'] ?? 0, 1); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">💬</div>
                <div class="stat-info">
                    <h3>Total Reviews</h3>
                    <p><?php echo $overall_stats['total_reviews'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Rate Departments</h2>
            </div>
            <p style="margin-bottom: 20px; color: #666;">Click on any department below to view its reviews or scroll down to submit a new review.</p>
            
            <div class="departments-grid">
                <?php foreach (get_departments() as $dept): ?>
                    <a href="department_page.php?dept=<?php echo urlencode($dept); ?>" class="dept-card">
                        <div class="dept-icon"><?php echo get_department_icon($dept); ?></div>
                        <h3><?php echo sanitize_output($dept); ?></h3>
                        <div class="dept-stats">
                            <span class="rating">
                                <?php 
                                $avg = isset($dept_stats[$dept]) ? number_format($dept_stats[$dept]['avg_rating'], 1) : 'N/A';
                                echo $avg == 'N/A' ? 'No ratings yet' : "⭐ $avg";
                                ?>
                            </span>
                            <span>
                                <?php 
                                $count = isset($dept_stats[$dept]) ? $dept_stats[$dept]['review_count'] : 0;
                                echo "$count review" . ($count != 1 ? 's' : '');
                                ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="section" id="submit-review">
            <div class="section-header">
                <h2>Submit Department Review</h2>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo sanitize_output($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo sanitize_output($error_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="#submit-review">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Select Department to Review</label>
                        <select name="review_department" required>
                            <option value="">-- Choose a department --</option>
                            <?php foreach (get_departments() as $dept): ?>
                                <option value="<?php echo sanitize_output($dept); ?>">
                                    <?php echo get_department_icon($dept); ?> <?php echo sanitize_output($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Rating</label>
                        <div class="star-rating" id="starRating">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Comment</label>
                        <textarea name="comment" rows="5" placeholder="Share your feedback about this department..." required></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Recent Reviews</h2>
            </div>
            
            <form method="GET" class="filter-bar">
                <select name="filter_dept">
                    <option value="">All Departments</option>
                    <?php foreach (get_departments() as $dept): ?>
                        <option value="<?php echo sanitize_output($dept); ?>" <?php echo ($filter_dept === $dept) ? 'selected' : ''; ?>>
                            <?php echo sanitize_output($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">🔍 Filter</button>
            </form>
            
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-department">
                                <span><?php echo get_department_icon($review['reviewer_department']); ?></span>
                                <span><?php echo sanitize_output($review['reviewer_department']); ?></span>
                            </div>
                            <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                        <div class="review-stars">
                            <?php for ($i = 0; $i < $review['rating']; $i++): ?>★<?php endfor; ?>
                            <?php for ($i = $review['rating']; $i < 5; $i++): ?>☆<?php endfor; ?>
                        </div>
                        <div class="review-comment"><?php echo nl2br(sanitize_output($review['comment'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📝</div>
                    <h3>No reviews yet</h3>
                    <p>Be the first to submit a review!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Star rating functionality
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;
                
                stars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = this.getAttribute('data-rating');
                stars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.style.color = '#ffc107';
                    }
                });
            });
        });
        
        document.getElementById('starRating').addEventListener('mouseleave', function() {
            const currentRating = ratingInput.value;
            stars.forEach(s => {
                if (s.getAttribute('data-rating') <= currentRating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    </script>
</body>
</html>
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
    
    if ($rating < 1 || $rating > 5) {
        $error_message = 'Please select a valid rating (1-5 stars).';
    } elseif (empty($comment)) {
        $error_message = 'Please provide a comment.';
    } else {
        $stmt = $conn->prepare("INSERT INTO reviews (reviewer_name, reviewer_department, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $user_name, $user_department, $rating, $comment);
        
        if ($stmt->execute()) {
            $success_message = 'Review submitted successfully!';
            log_activity($conn, $user_name, 'Submit Review', "Rating: $rating stars");
        } else {
            $error_message = 'Failed to submit review. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch reviews for user's department (excluding reviewer names)
$stmt = $conn->prepare("SELECT id, reviewer_department, rating, comment, created_at FROM reviews WHERE reviewer_department = ? AND visible_to_workers = 'Yes' ORDER BY created_at DESC");
$stmt->bind_param("s", $user_department);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate department statistics
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE reviewer_department = ? AND visible_to_workers = 'Yes'");
$stmt->bind_param("s", $user_department);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard - <?php echo sanitize_output($user_department); ?></title>
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
        
        .department-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .department-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .department-banner .icon {
            font-size: 80px;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .department-banner h2 {
            font-size: 32px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
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
            <a href="department_page.php?dept=<?php echo urlencode($user_department); ?>" style="text-decoration: none; color: white;">
                <div class="menu-item">
                    <span class="icon">📁</span>
                    <span>Department Page</span>
                </div>
            </a>
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
        
        <div class="department-banner">
            <div class="icon"><?php echo get_department_icon($user_department); ?></div>
            <h2><?php echo sanitize_output($user_department); ?></h2>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">⭐</div>
                <div class="stat-info">
                    <h3>Average Rating</h3>
                    <p><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">💬</div>
                <div class="stat-info">
                    <h3>Total Reviews</h3>
                    <p><?php echo $stats['total_reviews'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Submit New Review</h2>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo sanitize_output($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo sanitize_output($error_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" value="<?php echo sanitize_output($user_department); ?>" readonly>
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
                        <textarea name="comment" rows="5" placeholder="Share your feedback about the department..." required></textarea>
                    </div>
                    
                    <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2>Department Reviews</h2>
            </div>
            
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-department"><?php echo sanitize_output($review['reviewer_department']); ?></div>
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
                    <p>Be the first to submit a review for your department!</p>
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
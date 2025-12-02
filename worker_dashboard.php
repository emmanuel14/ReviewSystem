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
            $success_message = 'Thank you for your feedback!';
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
    $stmt = $conn->prepare("SELECT id, reviewer_department, rating, comment, created_at FROM reviews WHERE visible_to_workers = 'Yes' ORDER BY created_at DESC LIMIT 20");
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
    <title>Rate Today's Service - Church Workers</title>
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
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .navbar-icon {
            font-size: 40px;
        }
        
        .navbar-text h1 {
            font-size: 24px;
            color: #333;
            font-weight: 700;
        }
        
        .navbar-text p {
            font-size: 12px;
            color: #667eea;
        }
        
        .navbar-user {
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
            font-weight: 700;
            font-size: 18px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .user-dept {
            font-size: 11px;
            color: #666;
        }
        
        .logout-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }
        
        .page-header h2 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .page-header p {
            font-size: 18px;
            opacity: 0.95;
        }
        
        .departments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        .dept-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .dept-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border-color: #667eea;
        }
        
        .dept-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .dept-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .dept-info {
            flex: 1;
        }
        
        .dept-name {
            font-weight: 700;
            color: #333;
            font-size: 16px;
            margin-bottom: 3px;
        }
        
        .dept-subtitle {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dept-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .dept-rating {
            font-weight: 700;
            color: #667eea;
        }
        
        .star-rating {
            display: flex;
            gap: 8px;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .star {
            cursor: pointer;
            color: #ddd;
            transition: all 0.2s;
        }
        
        .star.active,
        .star:hover {
            color: #fbbf24;
            transform: scale(1.2);
        }
        
        .comment-box {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 13px;
            font-family: inherit;
            resize: none;
            transition: border-color 0.3s;
        }
        
        .comment-box:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .overall-section {
            max-width: 800px;
            margin: 0 auto 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            color: white;
        }
        
        .overall-section h3 {
            font-size: 32px;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .overall-select {
            width: 100%;
            padding: 15px;
            border: 2px solid white;
            border-radius: 12px;
            font-size: 16px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .overall-stars {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .overall-stars .star {
            font-size: 48px;
        }
        
        .overall-comment {
            width: 100%;
            padding: 15px;
            border: 2px solid white;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            resize: none;
            margin-bottom: 25px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .alert {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: white;
            color: #10b981;
            border: 3px solid #10b981;
        }
        
        .alert-error {
            background: white;
            color: #ef4444;
            border: 3px solid #ef4444;
        }
        
        .reviews-section {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .reviews-header h3 {
            font-size: 28px;
            color: #333;
        }
        
        .filter-select {
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .review-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
            transition: all 0.3s;
        }
        
        .review-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .review-dept {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #667eea;
            font-size: 15px;
        }
        
        .review-date {
            color: #999;
            font-size: 13px;
        }
        
        .review-stars {
            color: #fbbf24;
            font-size: 20px;
            margin-bottom: 12px;
        }
        
        .review-comment {
            color: #555;
            line-height: 1.7;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }
        
        .empty-icon {
            font-size: 100px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .departments-grid {
                grid-template-columns: 1fr;
            }
            
            .navbar-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .page-header h2 {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <div class="navbar-icon">⛪</div>
                <div class="navbar-text">
                    <h1>Church Rating System</h1>
                    <p>Rate Your Service Experience</p>
                </div>
            </div>
            
            <div class="navbar-user">
                <div class="user-info">
                    <div class="user-name"><?php echo sanitize_output($user_name); ?></div>
                    <div class="user-dept"><?php echo sanitize_output($user_department); ?></div>
                </div>
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h2>Rate Today's Service</h2>
            <p>Your feedback helps us serve better</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">✅ <?php echo sanitize_output($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">❌ <?php echo sanitize_output($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="ratingForm">
            <div class="departments-grid" id="departmentsGrid">
                <?php foreach (get_departments() as $dept): ?>
                    <div class="dept-card">
                        <div class="dept-header">
                            <div class="dept-icon" style="background: <?php 
                                $colors = ['#8B5CF6', '#EC4899', '#F59E0B', '#10B981', '#06B6D4', '#3B82F6'];
                                echo $colors[array_rand($colors)] . '20';
                            ?>;">
                                <?php echo get_department_icon($dept); ?>
                            </div>
                            <div class="dept-info">
                                <div class="dept-name"><?php echo sanitize_output($dept); ?></div>
                            </div>
                        </div>
                        
                        <div class="dept-stats">
                            <span class="dept-rating">
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
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="overall-section">
                <h3>Submit Your Review</h3>
                
                <select name="review_department" class="overall-select" required>
                    <option value="">-- Select Department to Review --</option>
                    <?php foreach (get_departments() as $dept): ?>
                        <option value="<?php echo sanitize_output($dept); ?>">
                            <?php echo get_department_icon($dept); ?> <?php echo sanitize_output($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="overall-stars">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="0">
                
                <textarea 
                    name="comment" 
                    class="overall-comment" 
                    rows="5" 
                    placeholder="Share your overall experience..." 
                    required
                ></textarea>
                
                <button type="submit" name="submit_review" class="submit-btn">Submit Rating</button>
            </div>
        </form>
        
        <div class="reviews-section">
            <div class="reviews-header">
                <h3>Recent Reviews</h3>
                <form method="GET">
                    <select name="filter_dept" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach (get_departments() as $dept): ?>
                            <option value="<?php echo sanitize_output($dept); ?>" <?php echo ($filter_dept === $dept) ? 'selected' : ''; ?>>
                                <?php echo sanitize_output($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-dept">
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
                    <div class="empty-icon">📝</div>
                    <h3>No reviews yet</h3>
                    <p>Be the first to submit a review!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Star rating functionality
        const stars = document.querySelectorAll('.overall-stars .star');
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
                        s.style.color = '#fbbf24';
                        s.style.transform = 'scale(1.2)';
                    }
                });
            });
        });
        
        document.querySelector('.overall-stars').addEventListener('mouseleave', function() {
            const currentRating = ratingInput.value;
            stars.forEach(s => {
                if (s.getAttribute('data-rating') <= currentRating) {
                    s.style.color = '#fbbf24';
                } else {
                    s.style.color = '#ddd';
                    s.style.transform = 'scale(1)';
                }
            });
        });
        
        // Form validation
        document.getElementById('ratingForm').addEventListener('submit', function(e) {
            const rating = document.getElementById('ratingInput').value;
            if (rating == 0) {
                e.preventDefault();
                alert('Please select a rating (1-5 stars)');
            }
        });
    </script>
</body>
</html>
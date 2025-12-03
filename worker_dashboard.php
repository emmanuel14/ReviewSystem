<?php
require_once 'config.php';
require_login();

$conn = getDBConnection();
$user_department = $_SESSION['department'];
$user_name = $_SESSION['user_name'];

// Handle multiple department reviews submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_all_reviews'])) {
    $overall_rating = intval($_POST['overall_rating'] ?? 0);
    $overall_comment = trim($_POST['overall_comment'] ?? '');
    
    $submitted_count = 0;
    $departments = get_departments();
    
    // Submit individual department reviews
    foreach ($departments as $index => $dept) {
        $rating = intval($_POST["rating_$index"] ?? 0);
        $comment = trim($_POST["comment_$index"] ?? '');
        
        if ($rating > 0 && !empty($comment)) {
            $stmt = $conn->prepare("INSERT INTO reviews (reviewer_name, reviewer_department, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $user_name, $dept, $rating, $comment);
            $stmt->execute();
            $stmt->close();
            $submitted_count++;
        }
    }
    
    // Submit overall service review if provided
    if ($overall_rating > 0 && !empty($overall_comment)) {
        $overall_dept = "Overall Service";
        $stmt = $conn->prepare("INSERT INTO reviews (reviewer_name, reviewer_department, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $user_name, $overall_dept, $overall_rating, $overall_comment);
        $stmt->execute();
        $stmt->close();
    }
    
    if ($submitted_count > 0 || ($overall_rating > 0 && !empty($overall_comment))) {
        $success_message = 'Thank you for your feedback!';
        log_activity($conn, $user_name, 'Submit Reviews', "Submitted $submitted_count department reviews");
        
        // Redirect to prevent resubmission
        header("Location: worker_dashboard.php?success=1");
        exit();
    } else {
        $error_message = 'Please rate at least one department.';
    }
}

// Check for success parameter
if (isset($_GET['success'])) {
    $success_message = 'Thank you for your feedback!';
}

// Calculate department statistics
$dept_stats_query = "SELECT reviewer_department, AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE visible_to_workers = 'Yes' GROUP BY reviewer_department";
$dept_stats_result = $conn->query($dept_stats_query);
$dept_stats = [];
while ($row = $dept_stats_result->fetch_assoc()) {
    $dept_stats[$row['reviewer_department']] = $row;
}

$conn->close();

$color_palette = ['#8B5CF6', '#EC4899', '#F59E0B', '#10B981', '#06B6D4', '#3B82F6', '#6366F1'];
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
            background: linear-gradient(to bottom right, #f3e7ff, #e0e7ff);
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(to right, #7c3aed, #2563eb, #4f46e5);
            color: white;
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
            height: 64px;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .navbar-icon {
            font-size: 32px;
        }
        
        .navbar-text h1 {
            font-size: 18px;
            font-weight: 700;
        }
        
        .navbar-text p {
            font-size: 12px;
            color: rgba(233, 213, 255, 0.9);
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 16px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .page-header h2 {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .page-header p {
            font-size: 16px;
            color: #6b7280;
        }
        
        .alert {
            max-width: 800px;
            margin: 0 auto 24px;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 14px;
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
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .departments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }
        
        .dept-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .dept-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
            border-color: #a78bfa;
        }
        
        .dept-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .dept-icon {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .dept-info {
            flex: 1;
        }
        
        .dept-name {
            font-weight: 700;
            color: #1f2937;
            font-size: 15px;
            margin-bottom: 2px;
        }
        
        .dept-subtitle {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .star-rating {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .star {
            cursor: pointer;
            color: #d1d5db;
            font-size: 20px;
            transition: all 0.2s;
        }
        
        .star.active {
            color: #fbbf24;
        }
        
        .star:hover {
            transform: scale(1.1);
        }
        
        .comment-box {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            resize: none;
            transition: border-color 0.3s;
        }
        
        .comment-box:focus {
            outline: none;
            border-color: #a78bfa;
        }
        
        .overall-section {
            max-width: 672px;
            margin: 0 auto;
            background: linear-gradient(to bottom right, #7c3aed, #2563eb);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .overall-section h3 {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 24px;
        }
        
        .overall-stars {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .overall-stars .star {
            font-size: 40px;
            color: #d1d5db;
        }
        
        .overall-stars .star.active {
            color: #fbbf24;
        }
        
        .overall-comment {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            resize: none;
            border: 2px solid white;
            margin-bottom: 24px;
        }
        
        .overall-comment:focus {
            outline: none;
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: white;
            color: #7c3aed;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .submit-btn:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .departments-grid {
                grid-template-columns: 1fr;
            }
            
            .navbar-container {
                flex-direction: column;
                height: auto;
                padding: 16px 20px;
                gap: 12px;
            }
            
            .page-header h2 {
                font-size: 28px;
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
                    <h1>Church Rating</h1>
                    <p>Welcome, <?php echo sanitize_output($user_name); ?></p>
                </div>
            </div>
            
            <div class="navbar-user">
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
            <div class="departments-grid">
                <?php 
                $departments = get_departments();
                foreach ($departments as $index => $dept): 
                    $color = $color_palette[$index % count($color_palette)];
                ?>
                    <div class="dept-card">
                        <div class="dept-header">
                            <div class="dept-icon" style="background-color: <?php echo $color; ?>20;">
                                <?php echo get_department_icon($dept); ?>
                            </div>
                            <div class="dept-info">
                                <div class="dept-name"><?php echo sanitize_output($dept); ?></div>
                                <div class="dept-subtitle">
                                    <?php 
                                    if (isset($dept_stats[$dept])) {
                                        echo number_format($dept_stats[$dept]['avg_rating'], 1) . " ⭐ • " . 
                                             $dept_stats[$dept]['review_count'] . " reviews";
                                    } else {
                                        echo "No ratings yet";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="star-rating" data-dept="<?php echo $index; ?>">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" name="rating_<?php echo $index; ?>" id="rating_<?php echo $index; ?>" value="0">
                        
                        <textarea 
                            name="comment_<?php echo $index; ?>" 
                            class="comment-box" 
                            rows="2" 
                            placeholder="Add your comments (optional)"
                        ></textarea>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="overall-section">
                <h3>Overall Service Rating</h3>
                
                <div class="overall-stars" id="overallStars">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
                <input type="hidden" name="overall_rating" id="overall_rating" value="0">
                
                <textarea 
                    name="overall_comment" 
                    class="overall-comment" 
                    rows="4" 
                    placeholder="Share your overall experience..."
                ></textarea>
                
                <button type="submit" name="submit_all_reviews" class="submit-btn">
                    Submit Ratings
                </button>
            </div>
        </form>
    </div>
    
    <script>
        // Individual department star ratings
        document.querySelectorAll('.dept-card .star-rating').forEach(ratingContainer => {
            const stars = ratingContainer.querySelectorAll('.star');
            const deptIndex = ratingContainer.getAttribute('data-dept');
            const hiddenInput = document.getElementById('rating_' + deptIndex);
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.getAttribute('data-rating');
                    hiddenInput.value = rating;
                    
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
                        }
                    });
                });
            });
            
            ratingContainer.addEventListener('mouseleave', function() {
                const currentRating = hiddenInput.value;
                stars.forEach(s => {
                    if (s.getAttribute('data-rating') > currentRating) {
                        s.style.color = '#d1d5db';
                    }
                });
            });
        });
        
        // Overall service star rating
        const overallStars = document.querySelectorAll('#overallStars .star');
        const overallInput = document.getElementById('overall_rating');
        
        overallStars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                overallInput.value = rating;
                
                overallStars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = this.getAttribute('data-rating');
                overallStars.forEach(s => {
                    if (s.getAttribute('data-rating') <= rating) {
                        s.style.color = '#fbbf24';
                    }
                });
            });
        });
        
        document.getElementById('overallStars').addEventListener('mouseleave', function() {
            const currentRating = overallInput.value;
            overallStars.forEach(s => {
                if (s.getAttribute('data-rating') > currentRating) {
                    s.style.color = '#d1d5db';
                }
            });
        });
    </script>
</body>
</html>
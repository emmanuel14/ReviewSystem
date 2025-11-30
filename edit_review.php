<?php
require_once 'config.php';
require_admin();

$conn = getDBConnection();
$review_id = intval($_GET['id'] ?? 0);
$success_message = '';
$error_message = '';

// Fetch review
$stmt = $conn->prepare("SELECT * FROM reviews WHERE id = ?");
$stmt->bind_param("i", $review_id);
$stmt->execute();
$result = $stmt->get_result();
$review = $result->fetch_assoc();
$stmt->close();

if (!$review) {
    header("Location: admin_dashboard.php");
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $visible = $_POST['visible_to_workers'] ?? 'Yes';
    
    if ($rating < 1 || $rating > 5) {
        $error_message = 'Please select a valid rating (1-5 stars).';
    } elseif (empty($comment)) {
        $error_message = 'Please provide a comment.';
    } else {
        $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ?, visible_to_workers = ? WHERE id = ?");
        $stmt->bind_param("issi", $rating, $comment, $visible, $review_id);
        
        if ($stmt->execute()) {
            $success_message = 'Review updated successfully!';
            log_activity($conn, $_SESSION['user_name'], 'Update Review', "Review ID: $review_id");
            
            // Refresh review data
            $stmt2 = $conn->prepare("SELECT * FROM reviews WHERE id = ?");
            $stmt2->bind_param("i", $review_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $review = $result2->fetch_assoc();
            $stmt2->close();
        } else {
            $error_message = 'Failed to update review. Please try again.';
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Review - Admin</title>
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
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
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
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
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
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .star-rating {
            display: flex;
            gap: 10px;
            font-size: 35px;
        }
        
        .star {
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }
        
        .star.active, .star:hover {
            color: #ffc107;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
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
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #17a2b8;
        }
        
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }
        
        .info-box strong {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Edit Review</h1>
            <p>Update review details and visibility</p>
        </div>
        
        <div class="content">
            <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo sanitize_output($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo sanitize_output($error_message); ?></div>
            <?php endif; ?>
            
            <div class="info-box">
                <p><strong>Reviewer:</strong> <?php echo sanitize_output($review['reviewer_name']); ?></p>
                <p><strong>Department:</strong> <?php echo sanitize_output($review['reviewer_department']); ?></p>
                <p><strong>Submitted:</strong> <?php echo date('F d, Y \a\t H:i', strtotime($review['created_at'])); ?></p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Rating</label>
                    <div class="star-rating" id="starRating">
                        <span class="star <?php echo $review['rating'] >= 1 ? 'active' : ''; ?>" data-rating="1">★</span>
                        <span class="star <?php echo $review['rating'] >= 2 ? 'active' : ''; ?>" data-rating="2">★</span>
                        <span class="star <?php echo $review['rating'] >= 3 ? 'active' : ''; ?>" data-rating="3">★</span>
                        <span class="star <?php echo $review['rating'] >= 4 ? 'active' : ''; ?>" data-rating="4">★</span>
                        <span class="star <?php echo $review['rating'] >= 5 ? 'active' : ''; ?>" data-rating="5">★</span>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="<?php echo $review['rating']; ?>">
                </div>
                
                <div class="form-group">
                    <label>Comment</label>
                    <textarea name="comment" rows="6" required><?php echo sanitize_output($review['comment']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Visible to Workers</label>
                    <select name="visible_to_workers">
                        <option value="Yes" <?php echo $review['visible_to_workers'] === 'Yes' ? 'selected' : ''; ?>>Yes - Visible to all workers</option>
                        <option value="No" <?php echo $review['visible_to_workers'] === 'No' ? 'selected' : ''; ?>>No - Hidden from workers</option>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
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
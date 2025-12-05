<?php
require_once 'config.php';
require_login();

$dept_name = $_GET['dept'] ?? '';

// Validate department
$valid_departments = get_departments();
if (!in_array($dept_name, $valid_departments)) {
    header("Location: worker_dashboard.php");
    exit();
}

$conn = getDBConnection();

// Fetch department reviews
$stmt = $conn->prepare("SELECT reviewer_department, rating, comment, created_at FROM reviews WHERE reviewer_department = ? AND visible_to_workers = 'Yes' ORDER BY created_at DESC LIMIT 20");
$stmt->bind_param("s", $dept_name);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate rating summary
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE reviewer_department = ? AND visible_to_workers = 'Yes'");
$stmt->bind_param("s", $dept_name);
$stmt->execute();
$summary_result = $stmt->get_result();
$summary = $summary_result->fetch_assoc();
$stmt->close();

// Rating distribution
$rating_dist = array_fill(1, 5, 0);
$stmt = $conn->prepare("SELECT rating, COUNT(*) as count FROM reviews WHERE reviewer_department = ? AND visible_to_workers = 'Yes' GROUP BY rating");
$stmt->bind_param("s", $dept_name);
$stmt->execute();
$dist_result = $stmt->get_result();
while ($row = $dist_result->fetch_assoc()) {
    $rating_dist[$row['rating']] = $row['count'];
}
$stmt->close();

$conn->close();

// Department descriptions
$dept_descriptions = [
    'Spirit & Life (THE WORD)' => 'Dedicated to teaching and preaching the Word of God with excellence and impact.',
    'Spirit & Power Ministry (MUSIC)' => 'Leading worship and creating an atmosphere of praise through music.',
    'The Fire Place (PRAYER)' => 'Interceding for the church and community through powerful prayer sessions.',
    'Be Well (HEALTH)' => 'Promoting health and wellness in our church community.',
    'Sanctuary Keepers (SANITATION)' => 'Maintaining a clean and welcoming environment for worship.',
    'Environment (SECURITY & SAFETY)' => 'Ensuring the safety and security of all church members and facilities.',
    'Dominion Membership Connect (EVANGELISM & FOLLOW-UP)' => 'Reaching out to the community and nurturing new believers.',
    'Training & Development' => 'Equipping workers with skills and knowledge for effective ministry.',
    'Dominion Impact Centre (USHERING + GREETERS + PROTOCOL + WELFARE)' => 'Creating a welcoming atmosphere and caring for visitor needs.',
    'Family Affairs & House Fellowship' => 'Strengthening family bonds and community through fellowship.',
    'Dominion Air Force (MEDIA)' => 'Broadcasting the gospel through various media channels.',
    'Sound & Light (TECHNICAL)' => 'Providing technical excellence for all church services.',
    'IT, Software & Electronics' => 'Managing technology infrastructure and digital solutions.',
    'Maintenance & Electrical' => 'Keeping facilities in excellent working condition.',
    'Creative Arts & Talents' => 'Expressing faith through various creative mediums.',
    'Sports, Entertainment & Outreach' => 'Engaging community through sports and entertainment.',
    'Junior Church (TEENS & CHILDREN)' => 'Nurturing the next generation in faith and character.',
    'General Services (FACILITY & ADMIN)' => 'Providing administrative support for church operations.'
];

$dept_desc = $dept_descriptions[$dept_name] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize_output($dept_name); ?> - Department Page</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><circle cx="200" cy="100" r="80" fill="rgba(255,255,255,0.1)"/><circle cx="900" cy="400" r="120" fill="rgba(255,255,255,0.1)"/><circle cx="600" cy="200" r="60" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .dept-icon {
            font-size: 100px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        .hero-content h1 {
            font-size: 42px;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .hero-content p {
            font-size: 18px;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .back-btn {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .summary-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .summary-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        
        .summary-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .summary-card .value {
            color: #333;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .summary-card .stars {
            color: #ffc107;
            font-size: 24px;
        }
        
        .rating-distribution {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 40px;
        }
        
        .rating-distribution h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 22px;
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
        }
        
        .bar-container {
            flex: 1;
            height: 25px;
            background: #f0f0f0;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }
        
        .reviews-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .reviews-header h2 {
            color: #333;
            font-size: 22px;
        }
        
        .submit-review-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .submit-review-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .review-card {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .review-stars {
            color: #ffc107;
            font-size: 18px;
        }
        
        .review-date {
            color: #999;
            font-size: 13px;
        }
        
        .review-comment {
            color: #555;
            line-height: 1.7;
            font-size: 15px;
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
            .hero-content h1 {
                font-size: 32px;
            }
            
            .summary-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="hero-content">
            <div class="dept-icon"><?php echo get_department_icon($dept_name); ?></div>
            <h1><?php echo sanitize_output($dept_name); ?></h1>
            <p><?php echo sanitize_output($dept_desc); ?></p>
            <a href="<?php echo is_admin() ? 'admin_dashboard.php' : 'worker_dashboard.php'; ?>" class="back-btn">← Back to Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <div class="summary-section">
            <div class="summary-card">
                <h3>Average Rating.</h3>
                <div class="value"><?php echo number_format($summary['avg_rating'], 1); ?></div>
                <div class="stars">
                    <?php 
                    $full_stars = floor($summary['avg_rating']);
                    for ($i = 0; $i < $full_stars; $i++) echo '★';
                    for ($i = $full_stars; $i < 5; $i++) echo '☆';
                    ?>
                </div>
            </div>
            
            <div class="summary-card">
                <h3>Total Reviews</h3>
                <div class="value"><?php echo $summary['total_reviews']; ?></div>
            </div>
            
            <div class="summary-card">
                <h3>Department</h3>
                <div class="value" style="font-size: 50px;"><?php echo get_department_icon($dept_name); ?></div>
            </div>
        </div>
        
        <div class="rating-distribution">
            <h2>Rating Distribution</h2>
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="rating-bar">
                    <div class="rating-label"><?php echo $i; ?> <?php for ($j = 0; $j < $i; $j++) echo '★'; ?></div>
                    <div class="bar-container">
                        <div class="bar-fill" style="width: <?php echo $summary['total_reviews'] > 0 ? ($rating_dist[$i] / $summary['total_reviews'] * 100) : 0; ?>%;">
                            <?php echo $rating_dist[$i]; ?>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
        
        <div class="reviews-section">
            <div class="reviews-header">
                <h2>Recent Reviews</h2>
                <?php if ($_SESSION['department'] === $dept_name): ?>
                    <a href="worker_dashboard.php#submit-review" class="submit-review-btn">+ Submit Review</a>
                <?php endif; ?>
            </div>
            
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-stars">
                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>★<?php endfor; ?>
                                <?php for ($i = $review['rating']; $i < 5; $i++): ?>☆<?php endfor; ?>
                            </div>
                            <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                        <div class="review-comment"><?php echo nl2br(sanitize_output($review['comment'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📝</div>
                    <h3>No reviews yet</h3>
                    <p>Be the first to review this department!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>g
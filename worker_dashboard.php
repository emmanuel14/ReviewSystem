<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard - CWPRS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: #f8f9fb;
            color: #333;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 240px;
            background: white;
            border-right: 1px solid #e5e7eb;
            padding: 24px 16px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0d9488 0%, #06b6d4 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            font-weight: 700;
        }
        
        .logo-text h3 {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .logo-text p {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 2px;
        }
        
        .nav-menu {
            flex: 1;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 8px;
            color: #6b7280;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .nav-item:hover {
            background: #f3f4f6;
            color: #1f2937;
        }
        
        .nav-item.active {
            background: #f0f9ff;
            color: #0369a1;
            border-left: 3px solid #0369a1;
            padding-left: 13px;
        }
        
        .nav-icon {
            font-size: 18px;
            width: 20px;
        }
        
        .logout {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-top: auto;
            color: #ef4444;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .logout:hover {
            background: #fee2e2;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 240px;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 20px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .header-title p {
            font-size: 14px;
            color: #6b7280;
        }
        
        .submit-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #001f3f 0%, #003d7a 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 31, 63, 0.3);
        }
        
        .content {
            padding: 32px;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #001f3f 0%, #003d7a 100%);
            border-radius: 12px;
            padding: 32px;
            color: white;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .hero-icon {
            font-size: 64px;
            opacity: 0.8;
        }
        
        .hero-text h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .hero-text p {
            font-size: 15px;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .stat-content h3 {
            font-size: 13px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .stat-change {
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .stat-change.positive {
            color: #10b981;
        }
        
        .stat-change.negative {
            color: #ef4444;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .reviews-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .reviews-header {
            padding: 24px 32px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .reviews-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .review-item {
            padding: 24px 32px;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        
        .review-item:hover {
            background: #f9fafb;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-title {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .review-text {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 12px;
            font-style: italic;
        }
        
        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .review-source {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .review-stars {
            display: flex;
            gap: 4px;
            font-size: 16px;
        }
        
        .star {
            color: #fbbf24;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                padding: 16px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .container {
                flex-direction: column;
            }
            
            .header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .hero-section {
                flex-direction: column;
                text-align: center;
            }
            
            .hero-icon {
                margin-bottom: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <div class="logo-icon">⛪</div>
                <div class="logo-text">
                    <h3>CWPRS</h3>
                    <p>Performance Review</p>
                </div>
            </div>
            
            <nav class="nav-menu">
                <a href="#" class="nav-item active">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">📝</span>
                    <span>Reviews</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">➕</span>
                    <span>Submit Review</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">👤</span>
                    <span>Profile</span>
                </a>
            </nav>
            
            <a href="#" class="logout">
                <span class="nav-icon">🚪</span>
                <span>Logout</span>
            </a>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-title">
                    <h1>Worker Dashboard</h1>
                    <p>Welcome back, here's your department's performance overview.</p>
                </div>
                <button class="submit-btn">
                    <span>📋</span>
                    <span>Submit a Review</span>
                </button>
            </div>
            
            <!-- Content -->
            <div class="content">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div>
                        <h2>Welcome & Hospitality Team</h2>
                    </div>
                    <div class="hero-icon">👥</div>
                </div>
                
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div>
                            <div class="stat-content">
                                <h3>⭐ Average Rating</h3>
                            </div>
                            <div class="stat-value">4.8</div>
                            <div class="stat-change positive">↑ +5.2% from last month</div>
                        </div>
                        <div class="stat-icon">⭐</div>
                    </div>
                    
                    <div class="stat-card">
                        <div>
                            <div class="stat-content">
                                <h3>📋 Total Reviews</h3>
                            </div>
                            <div class="stat-value">1,234</div>
                            <div class="stat-change negative">↓ -1.5% from last month</div>
                        </div>
                        <div class="stat-icon">📋</div>
                    </div>
                </div>
                
                <!-- Recent Reviews -->
                <div class="reviews-section">
                    <div class="reviews-header">
                        <h2>Recent Department Reviews</h2>
                    </div>
                    
                    <div class="review-item">
                        <div class="review-title">Excellent Service</div>
                        <div class="review-text">
                            "The hospitality team was incredibly welcoming and made our first visit feel like coming home. Their warmth and guidance were truly a blessing."
                        </div>
                        <div class="review-meta">
                            <span class="review-source">From: Guest Services Department</span>
                            <div class="review-stars">
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="review-item">
                        <div class="review-title">Areas for Improvement</div>
                        <div class="review-text">
                            "While the team was friendly, there seemed to be some confusion about event scheduling. Clearer communication would be beneficial."
                        </div>
                        <div class="review-meta">
                            <span class="review-source">From: Children's Ministry</span>
                            <div class="review-stars">
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="review-item">
                        <div class="review-title">Supportive and Organized</div>
                        <div class="review-text">
                            "The setup for the community outreach event was flawless. The team worked together seamlessly and created a wonderful atmosphere for everyone involved."
                        </div>
                        <div class="review-meta">
                            <span class="review-source">From: Outreach Department</span>
                            <div class="review-stars">
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                                <span class="star">★</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
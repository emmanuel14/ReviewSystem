<?php

require_once 'config.php';

// Initialize error message
$error = '';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: worker_dashboard.php");
    }
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $phone_number = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($phone_number) || empty($password)) {
        $error = 'Please enter both phone number and password.';
    } else {
        // Connect to database
        $conn = getDBConnection();
        
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, full_name, password, department, role FROM workers WHERE phone_number = ?");
        $stmt->bind_param("s", $phone_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password (assuming passwords are hashed with password_hash)
            if (password_verify($password, $user['password'])) {
                // Password is correct - set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['role'] = $user['role'];
                
                // Log the login activity
                log_activity($conn, $user['full_name'], 'Login', 'User logged in successfully');
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: worker_dashboard.php");
                }
                exit();
            } else {
                $error = 'Invalid phone number or password.';
            }
        } else {
            $error = 'Invalid phone number or password.';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Church Workers Login - Performance Review System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Left side - Image/Branding section */
        .login-image {
            flex: 1;
            background: linear-gradient(rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8)),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800"><rect fill="%23667eea" width="1200" height="800"/><circle cx="400" cy="300" r="150" fill="%23764ba2" opacity="0.3"/><circle cx="800" cy="500" r="200" fill="%23f093fb" opacity="0.2"/><circle cx="600" cy="150" r="100" fill="%23ffffff" opacity="0.1"/></svg>');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: white;
            text-align: center;
            position: relative;
        }
        
        .login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        }
        
        .church-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0); 
            }
            50% { 
                transform: translateY(-10px); 
            }
        }
        
        .login-image h1 {
            font-size: 32px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .login-image p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            line-height: 1.6;
        }
        
        /* Right side - Form section */
        .login-form-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-form-section h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .login-form-section > p {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #667eea;
            pointer-events: none;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder {
            color: #999;
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-message::before {
            content: '⚠️';
            font-size: 18px;
        }
        
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .form-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
        
        .features-list {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .features-list h3 {
            font-size: 14px;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 13px;
            opacity: 0.85;
        }
        
        .feature-item::before {
            content: '✓';
            background: rgba(255, 255, 255, 0.3);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-image {
                padding: 30px;
                min-height: 250px;
            }
            
            .login-image h1 {
                font-size: 24px;
            }
            
            .login-image p {
                font-size: 14px;
            }
            
            .church-icon {
                font-size: 60px;
            }
            
            .login-form-section {
                padding: 40px 30px;
            }
            
            .login-form-section h2 {
                font-size: 24px;
            }
            
            .features-list {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding/Image -->
        <div class="login-image">
            <div class="church-icon">⛪</div>
            <h1>Church Workers Portal</h1>
            <p>Performance Review & Department Engagement System</p>
            
            <div class="features-list">
                <h3>System Features:</h3>
                <div class="feature-item">Department Performance Reviews</div>
                <div class="feature-item">Real-time Analytics & Reports</div>
                <div class="feature-item">18 Ministry Departments</div>
                <div class="feature-item">Secure & Confidential</div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-form-section">
            <h2>Welcome Back</h2>
            <p>Sign in to access your dashboard</p>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo sanitize_output($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📱</span>
                        <input 
                            type="text" 
                            id="phone_number" 
                            name="phone_number" 
                            placeholder="Enter your phone number"
                            required
                            value="<?php echo isset($_POST['phone_number']) ? sanitize_output($_POST['phone_number']) : ''; ?>"
                            autocomplete="tel"
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>
                
                <button type="submit" class="login-btn">Sign In</button>
            </form>
            
            <div class="form-footer">
                Secure login with encrypted password protection
            </div>
        </div>
    </div>
</body>
</html>
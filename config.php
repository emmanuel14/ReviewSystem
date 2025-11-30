<?php
/**
 * Church Workers Performance Review System
 * Configuration File
 * 
 * This file contains database configuration and helper functions
 */

// Database Configuration
// UPDATE THESE VALUES WITH YOUR DATABASE CREDENTIALS
define('DB_HOST', 'localhost');           // Database host (usually 'localhost')
define('DB_USER', 'root');                // Your MySQL username
define('DB_PASS', '');                    // Your MySQL password
define('DB_NAME', 'church_workers_db');   // Your database name

/**
 * Create and return database connection
 * 
 * @return mysqli Database connection object
 */
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize output to prevent XSS attacks
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitize_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require user to be logged in
 * Redirects to login page if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Require user to be admin
 * Redirects to worker dashboard if not admin
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        header("Location: worker_dashboard.php");
        exit();
    }
}

/**
 * Log user activity to database
 * 
 * @param mysqli $conn Database connection
 * @param string $user_name Name of user performing action
 * @param string $action Action performed
 * @param string $details Additional details about the action
 */
function log_activity($conn, $user_name, $action, $details = '') {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_name, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user_name, $action, $details);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get list of all departments
 * 
 * @return array List of department names
 */
function get_departments() {
    return [
        'Spirit & Life (THE WORD)',
        'Spirit & Power Ministry (MUSIC)',
        'The Fire Place (PRAYER)',
        'Be Well (HEALTH)',
        'Sanctuary Keepers (SANITATION)',
        'Environment (SECURITY & SAFETY)',
        'Dominion Membership Connect (EVANGELISM & FOLLOW-UP)',
        'Training & Development',
        'Dominion Impact Centre (USHERING + GREETERS + PROTOCOL + WELFARE)',
        'Family Affairs & House Fellowship',
        'Dominion Air Force (MEDIA)',
        'Sound & Light (TECHNICAL)',
        'IT, Software & Electronics',
        'Maintenance & Electrical',
        'Creative Arts & Talents',
        'Sports, Entertainment & Outreach',
        'Junior Church (TEENS & CHILDREN)',
        'General Services (FACILITY & ADMIN)'
    ];
}

/**
 * Get icon emoji for a department
 * 
 * @param string $department Department name
 * @return string Icon emoji
 */
function get_department_icon($department) {
    $icons = [
        'Spirit & Life (THE WORD)' => '📖',
        'Spirit & Power Ministry (MUSIC)' => '🎵',
        'The Fire Place (PRAYER)' => '🔥',
        'Be Well (HEALTH)' => '🏥',
        'Sanctuary Keepers (SANITATION)' => '🧹',
        'Environment (SECURITY & SAFETY)' => '🛡️',
        'Dominion Membership Connect (EVANGELISM & FOLLOW-UP)' => '📢',
        'Training & Development' => '📚',
        'Dominion Impact Centre (USHERING + GREETERS + PROTOCOL + WELFARE)' => '🤝',
        'Family Affairs & House Fellowship' => '👨‍👩‍👧‍👦',
        'Dominion Air Force (MEDIA)' => '📹',
        'Sound & Light (TECHNICAL)' => '🎛️',
        'IT, Software & Electronics' => '💻',
        'Maintenance & Electrical' => '🔧',
        'Creative Arts & Talents' => '🎨',
        'Sports, Entertainment & Outreach' => '⚽',
        'Junior Church (TEENS & CHILDREN)' => '👶',
        'General Services (FACILITY & ADMIN)' => '🏢'
    ];
    
    return isset($icons[$department]) ? $icons[$department] : '⭐';
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @return string Formatted date
 */
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format datetime for display
 * 
 * @param string $datetime Datetime string
 * @return string Formatted datetime
 */
function format_datetime($datetime) {
    return date('M d, Y \a\t H:i', strtotime($datetime));
}

/**
 * Get user's initials for avatar
 * 
 * @param string $name Full name
 * @return string Initials (max 2 characters)
 */
function get_initials($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 1));
}

/**
 * Validate rating (must be 1-5)
 * 
 * @param int $rating Rating value
 * @return bool True if valid, false otherwise
 */
function is_valid_rating($rating) {
    return is_numeric($rating) && $rating >= 1 && $rating <= 5;
}

/**
 * Get star display for rating
 * 
 * @param int $rating Rating value (1-5)
 * @param bool $filled Use filled stars only
 * @return string Star display
 */
function get_stars($rating, $filled = false) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '★';
        } else {
            $stars .= $filled ? '' : '☆';
        }
    }
    return $stars;
}

/**
 * Clean phone number format
 * 
 * @param string $phone Phone number
 * @return string Cleaned phone number
 */
function clean_phone($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}

/**
 * Redirect with message
 * 
 * @param string $url URL to redirect to
 * @param string $message Message to display
 * @param string $type Message type (success, error, info)
 */
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Get and clear flash message
 * 
 * @return array|null Flash message array or null
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}

// Configuration complete
// All helper functions loaded
?>
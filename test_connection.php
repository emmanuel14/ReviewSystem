<?php
/**
 * Database Connection Test & Login Diagnostic
 * 
 * This file will help diagnose why you can't login
 * Upload this file and access it via browser
 * 
 * ⚠️ DELETE THIS FILE AFTER TROUBLESHOOTING!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Church Workers System Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #ddd; }
    .success { border-color: #28a745; }
    .error { border-color: #dc3545; background: #ffe6e6; }
    .warning { border-color: #ffc107; background: #fff8e1; }
    h2 { color: #333; margin-top: 30px; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    pre { background: #f8f8f8; padding: 10px; overflow-x: auto; }
</style>";

// Test 1: PHP Version
echo "<h2>Test 1: PHP Version</h2>";
echo "<div class='test " . (version_compare(PHP_VERSION, '7.4.0') >= 0 ? "success" : "error") . "'>";
echo "PHP Version: <strong>" . PHP_VERSION . "</strong>";
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo " ✅ (Compatible)";
} else {
    echo " ❌ (Need PHP 7.4+)";
}
echo "</div>";

// Test 2: MySQLi Extension
echo "<h2>Test 2: MySQLi Extension</h2>";
echo "<div class='test " . (extension_loaded('mysqli') ? "success" : "error") . "'>";
if (extension_loaded('mysqli')) {
    echo "✅ MySQLi extension is loaded";
} else {
    echo "❌ MySQLi extension is NOT loaded. Enable it in php.ini";
}
echo "</div>";

// Test 3: Database Configuration
echo "<h2>Test 3: Database Configuration</h2>";
$db_config = [
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'church_workers_db'
];

echo "<div class='test'>";
echo "<strong>Current Configuration:</strong><br>";
echo "Host: <code>{$db_config['DB_HOST']}</code><br>";
echo "User: <code>{$db_config['DB_USER']}</code><br>";
echo "Password: <code>" . (empty($db_config['DB_PASS']) ? '(empty)' : '***hidden***') . "</code><br>";
echo "Database: <code>{$db_config['DB_NAME']}</code>";
echo "</div>";

// Test 4: Database Connection
echo "<h2>Test 4: Database Connection</h2>";
$conn = @new mysqli($db_config['DB_HOST'], $db_config['DB_USER'], $db_config['DB_PASS'], $db_config['DB_NAME']);

if ($conn->connect_error) {
    echo "<div class='test error'>";
    echo "❌ <strong>Connection Failed!</strong><br>";
    echo "Error: " . $conn->connect_error . "<br><br>";
    echo "<strong>Possible Solutions:</strong><br>";
    echo "1. Make sure MySQL/MariaDB is running<br>";
    echo "2. Check your database credentials in config.php<br>";
    echo "3. Verify the database name exists<br>";
    echo "4. Check MySQL port (default: 3306)";
    echo "</div>";
    exit;
} else {
    echo "<div class='test success'>";
    echo "✅ Database connection successful!";
    echo "</div>";
}

// Test 5: Check if tables exist
echo "<h2>Test 5: Database Tables</h2>";

$tables = ['workers', 'reviews', 'activity_logs'];
$missing_tables = [];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<div class='test success'>✅ Table <code>$table</code> exists</div>";
    } else {
        echo "<div class='test error'>❌ Table <code>$table</code> does NOT exist</div>";
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<div class='test warning'>";
    echo "<strong>⚠️ Missing tables detected!</strong><br>";
    echo "Run the complete_database_setup.sql file to create all tables.";
    echo "</div>";
}

// Test 6: Check for admin accounts
echo "<h2>Test 6: Admin Accounts</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM workers WHERE role = 'admin'");
if ($result) {
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        echo "<div class='test success'>";
        echo "✅ Found {$row['count']} admin account(s)";
        echo "</div>";
        
        // Show admin accounts
        $admins = $conn->query("SELECT id, full_name, phone_number, department FROM workers WHERE role = 'admin'");
        echo "<div class='test'>";
        echo "<strong>Admin Accounts:</strong><br>";
        while ($admin = $admins->fetch_assoc()) {
            echo "• {$admin['full_name']} - Phone: <code>{$admin['phone_number']}</code><br>";
        }
        echo "</div>";
    } else {
        echo "<div class='test error'>";
        echo "❌ No admin accounts found
    }!<br>";
        echo "Create an admin account using the password hash generator.";
        echo "</div>";
    } }
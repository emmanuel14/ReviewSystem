<?php
/**
 * Fix All Accounts Script
 * Creates/Updates both Admin and Worker accounts
 * 
 * ⚠️ DELETE THIS FILE AFTER USE FOR SECURITY!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'church_workers_db');

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "<html><head><title>Fix All Accounts</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { background: #d4edda; border-left: 4px solid #28a745; }
.error { background: #f8d7da; border-left: 4px solid #dc3545; }
.info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
.warning { background: #fff3cd; border-left: 4px solid #ffc107; }
h1 { color: #333; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px; }
.btn { display: inline-block; padding: 12px 25px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; font-weight: bold; }
.btn:hover { background: #0056b3; }
.login-box { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
.login-box code { background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 3px; font-size: 16px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f8f9fa; font-weight: bold; }
</style></head><body>";

echo "<h1>🔧 Fix All Login Accounts</h1>";

// Check if workers table exists
$table_check = $conn->query("SHOW TABLES LIKE 'workers'");
if ($table_check->num_rows == 0) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERROR: Workers table does not exist!</h2>";
    echo "<p>Create the table first using complete_database_setup.sql</p>";
    echo "</div></body></html>";
    exit;
}

// Accounts to create/update
$accounts = [
    [
        'full_name' => 'System Admin',
        'phone_number' => '08012345678',
        'plain_password' => 'admin123',
        'department' => 'General Services (FACILITY & ADMIN)',
        'role' => 'admin'
    ],
    [
        'full_name' => 'Test Worker',
        'phone_number' => '08087654321',
        'plain_password' => 'worker123',
        'department' => 'Spirit & Life (THE WORD)',
        'role' => 'worker'
    ]
];

echo "<div class='box info'>";
echo "<h2>🔄 Processing Accounts...</h2>";

foreach ($accounts as $account) {
    echo "<h3>Processing: {$account['full_name']} ({$account['role']})</h3>";
    
    // Generate fresh password hash
    $hashed_password = password_hash($account['plain_password'], PASSWORD_DEFAULT);
    
    // Check if account exists
    $stmt = $conn->prepare("SELECT id FROM workers WHERE phone_number = ?");
    $stmt->bind_param("s", $account['phone_number']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows > 0) {
        // Update existing account
        echo "<p>📝 Account exists - Updating...</p>";
        
        $stmt = $conn->prepare("UPDATE workers SET full_name = ?, password = ?, department = ?, role = ? WHERE phone_number = ?");
        $stmt->bind_param("sssss", 
            $account['full_name'], 
            $hashed_password, 
            $account['department'], 
            $account['role'], 
            $account['phone_number']
        );
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Updated successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Update failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
        
    } else {
        // Create new account
        echo "<p>➕ Account doesn't exist - Creating...</p>";
        
        $stmt = $conn->prepare("INSERT INTO workers (full_name, phone_number, password, department, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", 
            $account['full_name'], 
            $account['phone_number'], 
            $hashed_password, 
            $account['department'], 
            $account['role']
        );
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Creation failed: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
    
    // Verify password
    $verify_stmt = $conn->prepare("SELECT password FROM workers WHERE phone_number = ?");
    $verify_stmt->bind_param("s", $account['phone_number']);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $verify_row = $verify_result->fetch_assoc();
    $verify_stmt->close();
    
    if ($verify_row && password_verify($account['plain_password'], $verify_row['password'])) {
        echo "<p style='color: green;'>🔐 Password verification: <strong>PASSED ✅</strong></p>";
    } else {
        echo "<p style='color: red;'>🔐 Password verification: <strong>FAILED ❌</strong></p>";
    }
    
    echo "<hr style='margin: 15px 0; border: none; border-top: 1px dashed #ddd;'>";
}

echo "</div>";

// Display login credentials
echo "<div class='login-box'>";
echo "<h2>🔑 YOUR LOGIN CREDENTIALS</h2>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;'>";

echo "<div style='background: rgba(255,255,255,0.1); padding: 15px; border-radius: 5px;'>";
echo "<h3 style='margin-top: 0;'>👨‍💼 ADMIN LOGIN</h3>";
echo "<p><strong>Phone:</strong> <code>08012345678</code></p>";
echo "<p><strong>Password:</strong> <code>admin123</code></p>";
echo "<p><strong>Access:</strong> Full System</p>";
echo "</div>";

echo "<div style='background: rgba(255,255,255,0.1); padding: 15px; border-radius: 5px;'>";
echo "<h3 style='margin-top: 0;'>👤 WORKER LOGIN</h3>";
echo "<p><strong>Phone:</strong> <code>08087654321</code></p>";
echo "<p><strong>Password:</strong> <code>worker123</code></p>";
echo "<p><strong>Access:</strong> Department Only</p>";
echo "</div>";

echo "</div>";
echo "<div style='margin-top: 20px; text-align: center;'>";
echo "<a href='login.php' class='btn' style='background: white; color: #667eea; font-size: 16px;'>🚀 GO TO LOGIN PAGE</a>";
echo "</div>";
echo "</div>";

// Verification table
echo "<div class='box success'>";
echo "<h2>✅ Account Verification</h2>";

$all_workers = $conn->query("SELECT id, full_name, phone_number, department, role, created_at FROM workers ORDER BY role DESC, id ASC");

if ($all_workers->num_rows > 0) {
    echo "<table>";
    echo "<thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Department</th><th>Role</th><th>Created</th></tr></thead>";
    echo "<tbody>";
    while ($worker = $all_workers->fetch_assoc()) {
        $role_badge = $worker['role'] == 'admin' 
            ? "<span style='background: #28a745; color: white; padding: 3px 10px; border-radius: 3px; font-size: 11px;'>ADMIN</span>"
            : "<span style='background: #6c757d; color: white; padding: 3px 10px; border-radius: 3px; font-size: 11px;'>WORKER</span>";
        
        echo "<tr>";
        echo "<td>" . $worker['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($worker['full_name']) . "</strong></td>";
        echo "<td><code>" . htmlspecialchars($worker['phone_number']) . "</code></td>";
        echo "<td>" . htmlspecialchars($worker['department']) . "</td>";
        echo "<td>" . $role_badge . "</td>";
        echo "<td>" . date('M d, Y', strtotime($worker['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    
    echo "<p style='margin-top: 20px; color: green; font-size: 16px;'><strong>✅ Total Accounts: " . $all_workers->num_rows . "</strong></p>";
} else {
    echo "<p style='color: red;'>❌ No accounts found in database!</p>";
}
echo "</div>";

// Test login simulation
echo "<div class='box info'>";
echo "<h2>🧪 Login Test Simulation</h2>";

foreach ($accounts as $account) {
    echo "<h3>Testing: {$account['phone_number']}</h3>";
    
    $stmt = $conn->prepare("SELECT id, full_name, password, department, role FROM workers WHERE phone_number = ?");
    $stmt->bind_param("s", $account['phone_number']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        echo "<pre>";
        echo "Account found: YES ✅\n";
        echo "Name: {$user['full_name']}\n";
        echo "Department: {$user['department']}\n";
        echo "Role: {$user['role']}\n";
        
        if (password_verify($account['plain_password'], $user['password'])) {
            echo "Password match: YES ✅\n";
            echo "\n<span style='color: green; font-weight: bold;'>LOGIN WILL SUCCEED! ✅</span>\n";
        } else {
            echo "Password match: NO ❌\n";
            echo "\n<span style='color: red; font-weight: bold;'>LOGIN WILL FAIL! ❌</span>\n";
        }
        echo "</pre>";
    } else {
        echo "<pre style='color: red;'>Account NOT found ❌</pre>";
    }
    
    $stmt->close();
    echo "<hr style='margin: 15px 0; border: none; border-top: 1px dashed #ddd;'>";
}
echo "</div>";

// Instructions
echo "<div class='box' style='background: #e7f3ff; border-left: 4px solid #2196F3;'>";
echo "<h2>📋 Next Steps</h2>";
echo "<ol style='line-height: 2;'>";
echo "<li>Click the <strong>'GO TO LOGIN PAGE'</strong> button above</li>";
echo "<li>For <strong>Admin access</strong>: Use phone <code>08012345678</code> and password <code>admin123</code></li>";
echo "<li>For <strong>Worker access</strong>: Use phone <code>08087654321</code> and password <code>worker123</code></li>";
echo "<li>If login still fails, check browser console for JavaScript errors</li>";
echo "<li><strong>DELETE THIS FILE</strong> after confirming login works!</li>";
echo "</ol>";
echo "</div>";

// Security warning
echo "<div class='box error'>";
echo "<h2>⚠️ CRITICAL SECURITY WARNING</h2>";
echo "<p style='font-size: 16px;'><strong>DELETE THIS FILE IMMEDIATELY</strong> after use to prevent unauthorized access to your system!</p>";
echo "</div>";
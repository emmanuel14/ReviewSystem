<?php


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

echo "<html><head><title>Fix Admin Account</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { background: #d4edda; border-left: 4px solid #28a745; }
.error { background: #f8d7da; border-left: 4px solid #dc3545; }
.info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
h1 { color: #333; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
.btn:hover { background: #0056b3; }
</style></head><body>";

echo "<h1>🔧 Admin Account Fix Script</h1>";

// Check if workers table exists
$table_check = $conn->query("SHOW TABLES LIKE 'workers'");
if ($table_check->num_rows == 0) {
    echo "<div class='box error'>";
    echo "<h2>❌ ERROR: Workers table does not exist!</h2>";
    echo "<p>You need to create the workers table first. Run this SQL:</p>";
    echo "<pre>CREATE TABLE workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    role ENUM('worker', 'admin') DEFAULT 'worker',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);</pre>";
    echo "</div></body></html>";
    exit;
}

// Generate fresh password hash
$plain_password = 'admin123';
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// Check if admin account exists
$check_admin = $conn->query("SELECT * FROM workers WHERE phone_number = '08012345678'");

if ($check_admin->num_rows > 0) {
    // Admin exists, update password
    echo "<div class='box info'>";
    echo "<h2>📝 Admin account exists - Updating password...</h2>";
    
    $stmt = $conn->prepare("UPDATE workers SET password = ? WHERE phone_number = '08012345678'");
    $stmt->bind_param("s", $hashed_password);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Admin password updated successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to update password: " . $stmt->error . "</p>";
    }
    $stmt->close();
    echo "</div>";
    
} else {
    // Admin doesn't exist, create it
    echo "<div class='box info'>";
    echo "<h2>➕ Creating new admin account...</h2>";
    
    $stmt = $conn->prepare("INSERT INTO workers (full_name, phone_number, password, department, role) VALUES (?, ?, ?, ?, ?)");
    $full_name = 'System Admin';
    $phone = '08012345678';
    $department = 'General Services (FACILITY & ADMIN)';
    $role = 'admin';
    
    $stmt->bind_param("sssss", $full_name, $phone, $hashed_password, $department, $role);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Admin account created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create account: " . $stmt->error . "</p>";
    }
    $stmt->close();
    echo "</div>";
}

// Verify the account
echo "<div class='box success'>";
echo "<h2>✅ Verification</h2>";

$verify = $conn->query("SELECT id, full_name, phone_number, department, role, password FROM workers WHERE phone_number = '08012345678'");

if ($verify->num_rows > 0) {
    $admin = $verify->fetch_assoc();
    echo "<p><strong>Account Details:</strong></p>";
    echo "<pre>";
    echo "ID: " . $admin['id'] . "\n";
    echo "Name: " . $admin['full_name'] . "\n";
    echo "Phone: " . $admin['phone_number'] . "\n";
    echo "Department: " . $admin['department'] . "\n";
    echo "Role: " . $admin['role'] . "\n";
    echo "Password Hash: " . substr($admin['password'], 0, 30) . "...\n";
    echo "</pre>";
    
    // Test password verification
    if (password_verify($plain_password, $admin['password'])) {
        echo "<p style='color: green; font-size: 18px;'><strong>✅ PASSWORD VERIFICATION SUCCESSFUL!</strong></p>";
        echo "<p>You can now login with these credentials:</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ PASSWORD VERIFICATION FAILED!</strong></p>";
        echo "<p>Something is wrong with the password hash.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Could not find admin account after creation/update!</p>";
}
echo "</div>";

// Login credentials box
echo "<div class='box' style='background: #e7f3ff; border-left: 4px solid #2196F3;'>";
echo "<h2>🔑 Your Login Credentials</h2>";
echo "<div style='font-size: 18px; margin: 20px 0;'>";
echo "<p><strong>Phone Number:</strong> <code style='background: white; padding: 5px 10px; border-radius: 3px;'>08012345678</code></p>";
echo "<p><strong>Password:</strong> <code style='background: white; padding: 5px 10px; border-radius: 3px;'>admin123</code></p>";
echo "</div>";
echo "<a href='login.php' class='btn' style='background: #28a745;'>🚀 Go to Login Page</a>";
echo "</div>";

// All workers list
echo "<div class='box'>";
echo "<h2>👥 All Workers in Database</h2>";
$all_workers = $conn->query("SELECT id, full_name, phone_number, department, role FROM workers ORDER BY role DESC, id ASC");

if ($all_workers->num_rows > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Name</th><th>Phone</th><th>Department</th><th>Role</th></tr>";
    while ($worker = $all_workers->fetch_assoc()) {
        $role_color = $worker['role'] == 'admin' ? '#28a745' : '#6c757d';
        echo "<tr>";
        echo "<td>" . $worker['id'] . "</td>";
        echo "<td>" . $worker['full_name'] . "</td>";
        echo "<td><strong>" . $worker['phone_number'] . "</strong></td>";
        echo "<td>" . $worker['department'] . "</td>";
        echo "<td><span style='background: $role_color; color: white; padding: 3px 10px; border-radius: 3px;'>" . strtoupper($worker['role']) . "</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No workers found in database.</p>";
}
echo "</div>";

// Warning
echo "<div class='box error'>";
echo "<h2>⚠️ IMPORTANT SECURITY WARNING</h2>";
echo "<p><strong>DELETE THIS FILE (fix_admin_account.php) IMMEDIATELY AFTER USE!</strong></p>";
echo "<p>This file can be used by anyone to reset admin passwords and is a security risk if left on your server.</p>";
echo "</div>";

$conn->close();
echo "</body></html>";
?>
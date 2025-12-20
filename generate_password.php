<?php


$generated_hash = '';
$password_to_hash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_to_hash = $_POST['password'] ?? '';
    if (!empty($password_to_hash)) {
        $generated_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
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
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .warning strong {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        p {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        input[type="text"], input[type="password"], textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            transition: border-color 0.3s;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .result-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #28a745;
            margin-top: 20px;
        }
        
        .result-box h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .hash-display {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .copy-btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .copy-btn:hover {
            background: #218838;
        }
        
        .examples {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .examples h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        
        .examples pre {
            background: white;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            border: 1px solid #90caf9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="warning">
            <strong>⚠️ SECURITY WARNING</strong>
            This file generates password hashes for your database. After creating your user accounts, 
            DELETE this file from your server immediately to prevent unauthorized access!
        </div>
        
        <h1>🔐 Password Hash Generator</h1>
        <p>Generate secure bcrypt hashes for worker passwords.</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">Enter Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter the password you want to hash"
                    value="<?php echo htmlspecialchars($password_to_hash); ?>"
                    required
                >
            </div>
            
            <button type="submit" class="btn">Generate Hash</button>
        </form>
        
        <?php if ($generated_hash): ?>
            <div class="result-box">
                <h3>✅ Generated Hash</h3>
                <div class="hash-display" id="hashOutput"><?php echo htmlspecialchars($generated_hash); ?></div>
                <button class="copy-btn" onclick="copyHash()">📋 Copy to Clipboard</button>
            </div>
        <?php endif; ?>
        
        <div class="examples">
            <h3>📝 SQL Insert Example</h3>
            <pre>-- Admin User
INSERT INTO workers (full_name, phone_number, password, department, role) 
VALUES (
    'John Admin', 
    '08012345678', 
    '<?php echo $generated_hash ? htmlspecialchars($generated_hash) : 'YOUR_GENERATED_HASH_HERE'; ?>', 
    'General Services (FACILITY & ADMIN)', 
    'admin'
);

-- Worker User
INSERT INTO workers (full_name, phone_number, password, department, role) 
VALUES (
    'Jane Worker', 
    '08087654321', 
    'YOUR_GENERATED_HASH_HERE', 
    'Spirit & Life (THE WORD)', 
    'worker'
);</pre>
        </div>
    </div>
    
    <script>
        function copyHash() {
            const hashText = document.getElementById('hashOutput').textContent;
            navigator.clipboard.writeText(hashText).then(() => {
                alert('Hash copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }
    </script>
</body>
</html>
<?php
require_once 'includes/auth.php';

// Simple password testing utility
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $auth = new Auth();
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
            echo "✅ Login successful for user: <strong>$username</strong><br>";
            echo "Role: " . $result['role'];
            echo "</div>";
            
            // Logout immediately for testing
            $auth->logout();
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
            echo "❌ Login failed: " . $result['message'];
            echo "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Password Test - Zero One Labs</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        input, button { padding: 10px; margin: 5px 0; width: 100%; box-sizing: border-box; }
        button { background: #007acc; color: white; border: none; cursor: pointer; }
        button:hover { background: #005a9e; }
        .demo-creds { background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>🔐 Password Authentication Test</h2>
    
    <div class="demo-creds">
        <h3>Demo Credentials:</h3>
        <p><strong>Admin:</strong> admin / admin123</p>
        <p><strong>Client:</strong> johndoe / client123</p>
        <p><em>Note: Run fix-passwords.php first if passwords aren't working</em></p>
    </div>
    
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Test Login</button>
    </form>
    
    <hr>
    
    <h3>🛠️ Setup Steps:</h3>
    <ol>
        <li>Run the database schema (schema.sql)</li>
        <li>Run <code>fix-passwords.php</code> to hash passwords properly</li>
        <li>Test login here or on the main login page</li>
        <li>Delete fix-passwords.php and test-password.php for security</li>
    </ol>
    
    <p><a href="login.php">← Back to Main Login</a></p>
</body>
</html>

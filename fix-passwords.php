<?php
require_once 'config/database.php';

// This script will fix the password hashing in the database
$database = new Database();
$conn = $database->getConnection();

// Define the correct passwords for demo users
$users_to_fix = [
    ['username' => 'admin', 'password' => 'admin123'],
    ['username' => 'johndoe', 'password' => 'client123']
];

echo "<h2>Fixing Password Hashes</h2>\n";

foreach ($users_to_fix as $user_data) {
    $username = $user_data['username'];
    $plain_password = $user_data['password'];
    
    // Generate proper hash
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    
    // Update the database
    $query = "UPDATE users SET password = :password WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':username', $username);
    
    if ($stmt->execute()) {
        echo "✅ Fixed password for user: <strong>$username</strong><br>\n";
        echo "   Plain password: $plain_password<br>\n";
        echo "   New hash: " . substr($hashed_password, 0, 30) . "...<br><br>\n";
    } else {
        echo "❌ Failed to fix password for user: <strong>$username</strong><br><br>\n";
    }
}

// Test the password verification
echo "<h3>Testing Password Verification:</h3>\n";

foreach ($users_to_fix as $user_data) {
    $username = $user_data['username'];
    $plain_password = $user_data['password'];
    
    // Get the user from database
    $query = "SELECT password FROM users WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $stored_hash = $user['password'];
        
        // Test verification
        if (password_verify($plain_password, $stored_hash)) {
            echo "✅ Password verification works for: <strong>$username</strong><br>\n";
        } else {
            echo "❌ Password verification failed for: <strong>$username</strong><br>\n";
        }
    }
}

echo "<br><strong>Password fix complete! You can now delete this file for security.</strong>\n";
?>

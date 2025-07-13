<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $message = "Please fill in all required fields";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match";
        $message_type = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long";
        $message_type = "error";
    } else {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if user exists
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $message = "Username or email already exists";
            $message_type = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (username, email, password, role, first_name, last_name, status) 
                     VALUES (:username, :email, :password, 'admin', :first_name, :last_name, 'active')";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            
            if ($stmt->execute()) {
                $message = "Admin account created successfully!";
                $message_type = "success";
            } else {
                $message = "Failed to create admin account. Please try again.";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account - Zero One Labs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --vscode-bg: #ffffff;
            --vscode-sidebar: #f3f3f3;
            --vscode-text: #24292f;
            --vscode-text-muted: #656d76;
            --vscode-border: #d0d7de;
            --vscode-accent: #0969da;
            --vscode-accent-hover: #0860ca;
            --vscode-card-bg: #ffffff;
            --vscode-input-bg: #ffffff;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--vscode-bg);
            color: var(--vscode-text);
        }

        .vscode-card {
            background: var(--vscode-card-bg);
            border: 1px solid var(--vscode-border);
        }

        .vscode-input {
            background: var(--vscode-input-bg);
            border: 1px solid var(--vscode-border);
            color: var(--vscode-text);
        }

        .vscode-button {
            background: var(--vscode-accent);
            color: white;
        }

        .vscode-button:hover {
            background: var(--vscode-accent-hover);
        }
    </style>
</head>
<body>
    <div class="min-h-screen p-6">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold">Create Admin Account</h1>
                    <p class="text-sm" style="color: var(--vscode-text-muted);">Add a new administrator to the system</p>
                </div>
                <a href="../dashboard-admin.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>

            <!-- Message Display -->
            <?php if ($message): ?>
            <div class="mb-6">
                <div class="p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="vscode-card rounded-lg p-6">
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium mb-2">Username *</label>
                            <input type="text" name="username" required class="vscode-input w-full px-3 py-2 rounded" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Email *</label>
                            <input type="email" name="email" required class="vscode-input w-full px-3 py-2 rounded" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">First Name *</label>
                            <input type="text" name="first_name" required class="vscode-input w-full px-3 py-2 rounded" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Last Name *</label>
                            <input type="text" name="last_name" required class="vscode-input w-full px-3 py-2 rounded" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Password *</label>
                            <input type="password" name="password" required class="vscode-input w-full px-3 py-2 rounded" minlength="8">
                            <p class="text-xs mt-1" style="color: var(--vscode-text-muted);">Minimum 8 characters</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Confirm Password *</label>
                            <input type="password" name="confirm_password" required class="vscode-input w-full px-3 py-2 rounded">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="../dashboard-admin.php" class="px-6 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                            Cancel
                        </a>
                        <button type="submit" class="vscode-button px-6 py-2 rounded">
                            <i class="fas fa-user-shield mr-2"></i>Create Admin Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

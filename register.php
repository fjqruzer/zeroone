<?php
require_once 'includes/auth.php';

$auth = new Auth();
$message = '';
$message_type = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: dashboard-$role.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $company = trim($_POST['company']);
    $phone = trim($_POST['phone']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $message = 'Please fill in all required fields';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long';
        $message_type = 'error';
    } else {
        $result = $auth->register($username, $email, $password, $first_name, $last_name, 'client', $company, $phone);
        
        if ($result['success']) {
            $message = 'Registration successful! Your account is pending admin approval. You will receive an email once approved.';
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Registration - Zero One Labs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --vscode-bg: #ffffff;
            --vscode-sidebar: #f3f3f3;
            --vscode-editor: #ffffff;
            --vscode-text: #24292f;
            --vscode-text-muted: #656d76;
            --vscode-border: #d0d7de;
            --vscode-accent: #0969da;
            --vscode-accent-hover: #0860ca;
            --vscode-input-bg: #ffffff;
            --vscode-input-border: #d0d7de;
            --vscode-button-bg: #f6f8fa;
            --vscode-button-hover: #f3f4f6;
            --vscode-shadow: rgba(31, 35, 40, 0.04);
        }

        [data-theme="dark"] {
            --vscode-bg: #1e1e1e;
            --vscode-sidebar: #252526;
            --vscode-editor: #1e1e1e;
            --vscode-text: #cccccc;
            --vscode-text-muted: #969696;
            --vscode-border: #3e3e42;
            --vscode-accent: #007acc;
            --vscode-accent-hover: #1177bb;
            --vscode-input-bg: #3c3c3c;
            --vscode-input-border: #3e3e42;
            --vscode-button-bg: #0e639c;
            --vscode-button-hover: #1177bb;
            --vscode-shadow: rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--vscode-bg);
            color: var(--vscode-text);
            transition: all 0.3s ease;
        }

        .vscode-window {
            background: var(--vscode-bg);
            border: 1px solid var(--vscode-border);
            box-shadow: 0 8px 32px var(--vscode-shadow);
        }

        .vscode-titlebar {
            background: var(--vscode-sidebar);
            border-bottom: 1px solid var(--vscode-border);
            height: 35px;
        }

        .vscode-input {
            background: var(--vscode-input-bg);
            border: 1px solid var(--vscode-input-border);
            color: var(--vscode-text);
        }

        .vscode-input:focus {
            border-color: var(--vscode-accent);
            outline: none;
            box-shadow: 0 0 0 1px var(--vscode-accent);
        }

        .vscode-button {
            background: var(--vscode-accent);
            color: white;
            border: none;
        }

        .vscode-button:hover {
            background: var(--vscode-accent-hover);
        }

        .vscode-button-secondary {
            background: var(--vscode-button-bg);
            color: var(--vscode-text);
            border: 1px solid var(--vscode-border);
        }

        .vscode-button-secondary:hover {
            background: var(--vscode-button-hover);
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body class="min-h-screen" data-theme="light">
    <!-- Theme Toggle -->
    <button onclick="toggleTheme()" class="theme-toggle vscode-button-secondary px-3 py-2 rounded-md transition-all duration-200">
        <i id="theme-icon" class="fas fa-moon"></i>
    </button>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl">
            <!-- VS Code Window -->
            <div class="vscode-window rounded-lg overflow-hidden">
                <!-- Title Bar -->
                <div class="vscode-titlebar flex items-center px-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    </div>
                    <div class="flex-1 text-center">
                        <span class="text-sm font-medium" style="color: var(--vscode-text);">Zero One Labs - Client Registration</span>
                    </div>
                </div>

                <div class="flex min-h-[600px]">
                    <!-- Activity Bar -->
                    <div class="w-12 flex flex-col items-center py-4 space-y-4" style="background: var(--vscode-sidebar); border-right: 1px solid var(--vscode-border);">
                        <div class="w-6 h-6 flex items-center justify-center text-white bg-green-600 rounded">
                            <i class="fas fa-user-plus text-xs"></i>
                        </div>
                        <div class="w-6 h-6 flex items-center justify-center" style="color: var(--vscode-text-muted);">
                            <i class="fas fa-sign-in-alt text-xs"></i>
                        </div>
                        <div class="w-6 h-6 flex items-center justify-center" style="color: var(--vscode-text-muted);">
                            <i class="fas fa-home text-xs"></i>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="w-64 p-4" style="background: var(--vscode-sidebar); border-right: 1px solid var(--vscode-border);">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-code text-blue-500 mr-2"></i>
                            <span class="font-semibold text-sm">ZERO ONE LABS</span>
                        </div>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center py-1" style="color: var(--vscode-text-muted);">
                                <i class="fas fa-folder-open mr-2 text-xs"></i>
                                <span>registration/</span>
                            </div>
                            <div class="flex items-center py-1 pl-4 bg-blue-100 rounded" style="color: var(--vscode-accent);">
                                <i class="fas fa-file mr-2 text-xs"></i>
                                <span>register.php</span>
                            </div>
                            <div class="flex items-center py-1 pl-4" style="color: var(--vscode-text-muted);">
                                <i class="fas fa-file mr-2 text-xs"></i>
                                <span>login.php</span>
                            </div>
                            <div class="flex items-center py-1" style="color: var(--vscode-text-muted);">
                                <i class="fas fa-folder mr-2 text-xs"></i>
                                <span>dashboard/</span>
                            </div>
                        </div>

                        <div class="mt-6 p-3 rounded text-xs" style="background: var(--vscode-input-bg); border: 1px solid var(--vscode-border);">
                            <div class="font-mono" style="color: var(--vscode-text-muted);">
                                <div class="text-blue-400">→ New Account</div>
                                <div class="text-yellow-400">⚡ Secure Registration</div>
                                <div class="text-green-400">✓ Admin Approval</div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="flex-1 flex flex-col" style="background: var(--vscode-editor);">
                        <!-- Breadcrumb -->
                        <div class="px-6 py-3 flex items-center text-sm border-b" style="border-color: var(--vscode-border); color: var(--vscode-text-muted);">
                            <span>registration</span>
                            <i class="fas fa-chevron-right mx-2 text-xs"></i>
                            <span>register.php</span>
                        </div>

                        <!-- Registration Form -->
                        <div class="flex-1 p-6">
                            <div class="max-w-2xl mx-auto">
                                <div class="mb-6">
                                    <h1 class="text-3xl font-bold mb-2">Create Account</h1>
                                    <p style="color: var(--vscode-text-muted);">Join Zero One Labs and start your development journey</p>
                                </div>

                                <?php if ($message): ?>
                                <div class="mb-6 p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                                <?php endif; ?>

                                <form method="POST" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="first_name" class="block text-sm font-medium mb-2">First Name *</label>
                                            <input type="text" id="first_name" name="first_name" required 
                                                   class="vscode-input w-full px-3 py-2 rounded text-sm transition-all duration-200"
                                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                        </div>
                                        <div>
                                            <label for="last_name" class="block text-sm font-medium mb-2">Last Name *</label>
                                            <input type="text" id="last_name" name="last_name" required 
                                                   class="vscode-input w-full px-3 py-2 rounded text-sm transition-all duration-200"
                                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="username" class="block text-sm font-medium mb-2">Username *</label>
                                            <input type="text" id="username" name="username" required 
                                                   class="vscode-input w-full px-3 py-2 rounded text-sm transition-all duration-200"
                                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                        </div>
                                        <div>
                                            <label for="email" class="block text-sm font-medium mb-2">Email *</label>
                                            <input type="email" id="email" name="email" required 
                                                   class="vscode-input w-full px-3 py-2 rounded text-sm transition-all duration-200"
                                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="company" class="block text-sm font-medium mb-2">Company</label>
                                            <input type="text" id="company" name="company" 
                                                   class="vscode-input w-full px-3 py-2 rounded text-sm transition-all duration-200"
                                                   value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : ''; ?>">
                                        </div>
                                        <div>
                                            <label for="phone" class="block text-sm font-medium mb-2">Phone</label>
                                            <input type="tel" id="phone" name="phone" 
                                                   class="vscode-input w-full px-3 py-2 rounded text-sm transition-all duration-200"
                                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="password" class="block text-sm font-medium mb-2">Password *</label>
                                            <div class="relative">
                                                <input type="password" id="password" name="password" required 
                                                       class="vscode-input w-full px-3 py-2 rounded text-sm pr-10 transition-all duration-200"
                                                       minlength="6">
                                                <button type="button" onclick="togglePassword('password')" 
                                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 transition-colors"
                                                        style="color: var(--vscode-text-muted);">
                                                    <i id="password-icon" class="fas fa-eye text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label for="confirm_password" class="block text-sm font-medium mb-2">Confirm Password *</label>
                                            <div class="relative">
                                                <input type="password" id="confirm_password" name="confirm_password" required 
                                                       class="vscode-input w-full px-3 py-2 rounded text-sm pr-10 transition-all duration-200"
                                                       minlength="6">
                                                <button type="button" onclick="togglePassword('confirm_password')" 
                                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 transition-colors"
                                                        style="color: var(--vscode-text-muted);">
                                                    <i id="confirm_password-icon" class="fas fa-eye text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-4">
                                        <button type="submit" class="vscode-button flex-1 py-3 px-4 rounded text-sm font-medium transition-all duration-200">
                                            <i class="fas fa-user-plus mr-2"></i>Create Account
                                        </button>
                                        <a href="login.php" class="vscode-button-secondary flex-1 py-3 px-4 rounded text-sm font-medium text-center transition-all duration-200">
                                            <i class="fas fa-sign-in-alt mr-2"></i>Already have an account?
                                        </a>
                                    </div>
                                </form>

                                <div class="mt-6 text-center">
                                    <a href="public/index.php" class="text-sm hover:underline" style="color: var(--vscode-text-muted);">
                                        <i class="fas fa-arrow-left mr-1"></i>Back to Homepage
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Bar -->
                <div class="flex items-center justify-between px-4 text-xs text-white" style="height: 22px; background: var(--vscode-accent);">
                    <div class="flex items-center space-x-4">
                        <span><i class="fas fa-user-plus mr-1"></i>Registration</span>
                        <span><i class="fas fa-shield-alt mr-1"></i>Secure</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span>PHP 8.2</span>
                        <span>UTF-8</span>
                        <span>Ln 1, Col 1</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTheme() {
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            const currentTheme = body.getAttribute('data-theme');
            
            if (currentTheme === 'light') {
                body.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
            } else {
                body.setAttribute('data-theme', 'light');
                themeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
            }
        }

        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(fieldId + '-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash text-xs';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye text-xs';
            }
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const body = document.body;
            const themeIcon = document.getElementById('theme-icon');
            
            body.setAttribute('data-theme', savedTheme);
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);
            // You can add visual feedback here
        });

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            return strength;
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>

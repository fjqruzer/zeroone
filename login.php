<?php
require_once 'includes/auth.php';

$auth = new Auth();
$error_message = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: dashboard-$role.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            header("Location: dashboard-{$result['role']}.php");
            exit();
        } else {
            $error_message = $result['message'];
        }
    } else {
        $error_message = 'Please fill in all fields';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zero One Labs - Developer Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* VS Code Light Theme */
            --vscode-bg: #ffffff;
            --vscode-sidebar: #f3f3f3;
            --vscode-editor: #ffffff;
            --vscode-text: #24292f;
            --vscode-text-muted: #656d76;
            --vscode-border: #d0d7de;
            --vscode-accent: #0969da;
            --vscode-accent-hover: #0860ca;
            --vscode-success: #1a7f37;
            --vscode-warning: #d1242f;
            --vscode-input-bg: #ffffff;
            --vscode-input-border: #d0d7de;
            --vscode-button-bg: #f6f8fa;
            --vscode-button-hover: #f3f4f6;
            --vscode-shadow: rgba(31, 35, 40, 0.04);
        }

        [data-theme="dark"] {
            /* VS Code Dark Theme */
            --vscode-bg: #1e1e1e;
            --vscode-sidebar: #252526;
            --vscode-editor: #1e1e1e;
            --vscode-text: #cccccc;
            --vscode-text-muted: #969696;
            --vscode-border: #3e3e42;
            --vscode-accent: #007acc;
            --vscode-accent-hover: #1177bb;
            --vscode-success: #89d185;
            --vscode-warning: #f85149;
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

        .font-mono {
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
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

        .activity-bar {
            width: 48px;
            background: var(--vscode-sidebar);
            border-right: 1px solid var(--vscode-border);
        }

        .sidebar {
            width: 300px;
            background: var(--vscode-sidebar);
            border-right: 1px solid var(--vscode-border);
        }

        .editor-area {
            background: var(--vscode-editor);
            flex: 1;
        }

        .status-bar {
            height: 22px;
            background: var(--vscode-accent);
            color: white;
            font-size: 12px;
        }

        .breadcrumb {
            background: var(--vscode-editor);
            border-bottom: 1px solid var(--vscode-border);
            color: var(--vscode-text-muted);
            font-size: 13px;
        }

        .terminal-like {
            background: #0c0c0c;
            color: #cccccc;
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="min-h-screen" data-theme="light">
    <!-- Theme Toggle -->
    <button onclick="toggleTheme()" class="theme-toggle vscode-button-secondary px-3 py-2 rounded-md transition-all duration-200">
        <i id="theme-icon" class="fas fa-moon"></i>
    </button>

    <div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-6xl">
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
                    <span class="text-sm font-medium hidden sm:inline" style="color: var(--vscode-text);">Zero One Labs - Authentication Portal</span>
                    <span class="text-sm font-medium sm:hidden" style="color: var(--vscode-text);">Zero One Labs</span>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row min-h-[400px] lg:h-96">
                <!-- Activity Bar -->
                <div class="activity-bar hidden lg:flex flex-col items-center py-4 space-y-4">
                    <div class="w-6 h-6 flex items-center justify-center text-white bg-blue-600 rounded">
                        <i class="fas fa-user text-xs"></i>
                    </div>
                    <div class="w-6 h-6 flex items-center justify-center" style="color: var(--vscode-text-muted);">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                    <div class="w-6 h-6 flex items-center justify-center" style="color: var(--vscode-text-muted);">
                        <i class="fas fa-code-branch text-xs"></i>
                    </div>
                    <div class="w-6 h-6 flex items-center justify-center" style="color: var(--vscode-text-muted);">
                        <i class="fas fa-bug text-xs"></i>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="sidebar hidden md:block">
                    <div class="p-4">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-code text-blue-500 mr-2"></i>
                            <span class="font-semibold text-sm lg:text-base">ZERO ONE LABS</span>
                        </div>
                        
                        <div class="space-y-2 text-xs lg:text-sm">
                            <div class="flex items-center py-1" style="color: var(--vscode-text-muted);">
                                <i class="fas fa-folder-open mr-2 text-xs"></i>
                                <span>authentication/</span>
                            </div>
                            <div class="flex items-center py-1 pl-4" style="color: var(--vscode-text-muted);">
                                <i class="fas fa-file mr-2 text-xs"></i>
                                <span>login.php</span>
                            </div>
                            <div class="flex items-center py-1 pl-4" style="color: var(--vscode-text-muted);">
                                <i class="fas fa-file mr-2 text-xs"></i>
                                <span>dashboard.php</span>
                            </div>
                            <div class="flex items-center py-1" style="color: var(--vscode-text-muted);">
                                <i class="fas fa-folder mr-2 text-xs"></i>
                                <span>config/</span>
                            </div>
                            <div class="flex items-center py-1" style="color: var(--vscode-text-muted);">
                                <i class="fas fa-folder mr-2 text-xs"></i>
                                <span>includes/</span>
                            </div>
                        </div>

                        <div class="mt-6 p-3 rounded text-xs" style="background: var(--vscode-input-bg); border: 1px solid var(--vscode-border);">
                            <div class="font-mono" style="color: var(--vscode-text-muted);">
                                <div class="text-green-400">✓ Database Connected</div>
                                <div class="text-blue-400">→ Session Active</div>
                                <div class="text-yellow-400">⚡ SSL Enabled</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Editor Area -->
                <div class="editor-area flex flex-col flex-1">
                    <!-- Breadcrumb -->
                    <div class="breadcrumb px-4 py-2 flex items-center text-xs">
                        <span>authentication</span>
                        <i class="fas fa-chevron-right mx-2 text-xs"></i>
                        <span>login.php</span>
                    </div>

                    <!-- Login Form -->
                    <div class="flex-1 p-4 lg:p-6">
                        <div class="max-w-md mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold mb-2">Developer Portal</h1>
        <p style="color: var(--vscode-text-muted);">Access your Zero One Labs workspace</p>
    </div>

    <form method="POST" class="space-y-4">
        <div>
            <label for="username" class="block text-sm font-medium mb-2">
                Username or Email
            </label>
            <input 
                type="text" 
                id="username" 
                name="username" 
                required
                class="vscode-input w-full px-3 py-2 rounded text-sm transition-all duration-200"
                placeholder="Enter your credentials"
                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
            >
        </div>

        <div>
            <label for="password" class="block text-sm font-medium mb-2">
                Password
            </label>
            <div class="relative">
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    class="vscode-input w-full px-3 py-2 rounded text-sm pr-10 transition-all duration-200"
                    placeholder="Enter your password"
                >
                <button 
                    type="button" 
                    onclick="togglePassword()"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 transition-colors"
                    style="color: var(--vscode-text-muted);"
                >
                    <i id="password-icon" class="fas fa-eye text-xs"></i>
                </button>
            </div>
        </div>

        <?php if ($error_message): ?>
        <div class="p-3 rounded text-sm" style="background: rgba(248, 81, 73, 0.1); border: 1px solid #f85149; color: #f85149;">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <button 
            type="submit"
            class="vscode-button w-full py-2 px-4 rounded text-sm font-medium transition-all duration-200"
        >
            Sign In to Workspace
        </button>
    </form>
</div>
                    </div>
                </div>
            </div>

            <!-- Status Bar -->
            <div class="status-bar flex items-center justify-between px-4">
                <div class="flex items-center space-x-2 lg:space-x-4 text-xs">
                    <span class="hidden sm:inline"><i class="fas fa-code-branch mr-1"></i>main</span>
                    <span><i class="fas fa-sync mr-1"></i>Synced</span>
                    <span class="hidden md:inline"><i class="fas fa-shield-alt mr-1"></i>Secure</span>
                </div>
                <div class="flex items-center space-x-2 lg:space-x-4 text-xs">
                    <span class="hidden sm:inline">PHP 8.2</span>
                    <span class="hidden md:inline">UTF-8</span>
                    <span>Ln 42, Col 16</span>
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

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
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

        // Add loading state to form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Authenticating...';
            submitBtn.disabled = true;
        });

        // Simulate typing effect for demo
        function typeWriter(element, text, speed = 50) {
            let i = 0;
            element.innerHTML = '';
            function type() {
                if (i < text.length) {
                    element.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            type();
        }
    </script>
</body>
</html>

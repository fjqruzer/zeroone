<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$database = new Database();
$conn = $database->getConnection();

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_user':
                $user_id = $_POST['user_id'];
                $query = "UPDATE users SET status = 'active' WHERE id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                if ($stmt->execute()) {
                    $message = "User approved successfully";
                    $message_type = "success";
                }
                break;
                
            case 'reject_user':
                $user_id = $_POST['user_id'];
                $query = "UPDATE users SET status = 'inactive' WHERE id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                if ($stmt->execute()) {
                    $message = "User rejected";
                    $message_type = "warning";
                }
                break;
                
            case 'update_project_status':
                $project_id = $_POST['project_id'];
                $status = $_POST['status'];
                $query = "UPDATE projects SET status = :status WHERE id = :project_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':project_id', $project_id);
                if ($stmt->execute()) {
                    $message = "Project status updated";
                    $message_type = "success";
                }
                break;
                
            case 'delete_project':
                $project_id = $_POST['project_id'];
                $query = "DELETE FROM projects WHERE id = :project_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':project_id', $project_id);
                if ($stmt->execute()) {
                    $message = "Project deleted";
                    $message_type = "success";
                }
                break;
        }
    }
}

// Get comprehensive statistics
$stats = [];

// Total clients
$query = "SELECT COUNT(*) as count FROM users WHERE role = 'client'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total projects
$query = "SELECT COUNT(*) as count FROM projects";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Active projects
$query = "SELECT COUNT(*) as count FROM projects WHERE status = 'in_progress'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['active_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Pending approvals
$query = "SELECT COUNT(*) as count FROM users WHERE status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['pending_approvals'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Revenue calculation (sum of project budgets)
$query = "SELECT COALESCE(SUM(budget), 0) as revenue FROM projects WHERE status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

// Support tickets statistics
$query = "SELECT COUNT(*) as count FROM support_tickets";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_tickets'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$query = "SELECT COUNT(*) as count FROM support_tickets WHERE status = 'open'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['open_tickets'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$query = "SELECT COUNT(*) as count FROM support_tickets WHERE status = 'in_progress'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['in_progress_tickets'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Inquiries statistics
$query = "SELECT COUNT(*) as count FROM inquiries";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['total_inquiries'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$query = "SELECT COUNT(*) as count FROM inquiries WHERE status = 'new'";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['new_inquiries'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent projects with full details
$query = "SELECT p.*, u.first_name, u.last_name, u.company, u.email FROM projects p 
          JOIN users u ON p.client_id = u.id 
          ORDER BY p.created_at DESC LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pending users for approval
$query = "SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent client registrations
$query = "SELECT * FROM users WHERE role = 'client' ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent support tickets
$query = "SELECT st.*, u.first_name, u.last_name, u.email, u.company 
          FROM support_tickets st 
          JOIN users u ON st.client_id = u.id 
          ORDER BY st.created_at DESC LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Zero One Labs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --vscode-card-bg: #ffffff;
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
            --vscode-card-bg: #252526;
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

        .vscode-card {
            background: var(--vscode-card-bg);
            border: 1px solid var(--vscode-border);
            box-shadow: 0 2px 8px var(--vscode-shadow);
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
            width: 60px;
        }

        @media (min-width: 1024px) {
            .sidebar {
                width: 240px;
            }
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

        .nav-item {
            color: var(--vscode-text-muted);
            transition: all 0.2s ease;
        }

        .nav-item:hover, .nav-item.active {
            color: var(--vscode-text);
            background: var(--vscode-button-bg);
        }

        .stat-card {
            background: var(--vscode-card-bg);
            border: 1px solid var(--vscode-border);
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            border-color: var(--vscode-accent);
            box-shadow: 0 4px 12px var(--vscode-shadow);
        }

        /* VS Code Toolbar Styles */
        .vscode-toolbar {
            background: var(--vscode-editor);
            border-bottom: 1px solid var(--vscode-border);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 0 8px;
            border-right: 1px solid var(--vscode-border);
        }

        .toolbar-group:last-child {
            border-right: none;
        }

        .toolbar-button {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid transparent;
            border-radius: 4px;
            background: transparent;
            color: var(--vscode-text);
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .toolbar-button:hover {
            background: var(--vscode-button-bg);
            border-color: var(--vscode-border);
        }

        .toolbar-button.primary {
            background: var(--vscode-accent);
            color: white;
        }

        .toolbar-button.primary:hover {
            background: var(--vscode-accent-hover);
        }

        .toolbar-button.danger {
            color: var(--vscode-warning);
        }

        .toolbar-button.danger:hover {
            background: rgba(248, 81, 73, 0.1);
        }

        .toolbar-separator {
            width: 1px;
            height: 20px;
            background: var(--vscode-border);
            margin: 0 4px;
        }

        /* Fix table hover styles */
        .table-row-hover:hover {
            background-color: var(--vscode-sidebar) !important;
        }
    </style>
</head>
<body data-theme="light">
    <!-- Theme Toggle -->
    <button onclick="toggleTheme()" class="theme-toggle vscode-button-secondary px-3 py-2 rounded-md transition-all duration-200">
        <i id="theme-icon" class="fas fa-moon"></i>
    </button>

    <div class="flex h-screen">
        <!-- Activity Bar -->
        <div class="activity-bar flex flex-col items-center py-4 space-y-4">
            <div class="w-6 h-6 flex items-center justify-center text-white bg-blue-600 rounded">
                <i class="fas fa-tachometer-alt text-xs"></i>
            </div>
            <div class="w-6 h-6 flex items-center justify-center" style="color: var(--vscode-text-muted);">
                <i class="fas fa-users text-xs"></i>
            </div>
            <div class="w-6 h-6 flex items-center justify-center" style="color: var(--vscode-text-muted);">
                <i class="fas fa-project-diagram text-xs"></i>
            </div>
            <div class="w-6 h-6 flex items-center justify-center" style="color: var(--vscode-text-muted);">
                <i class="fas fa-cog text-xs"></i>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="sidebar flex flex-col">
            <div class="p-3 lg:p-4 border-b" style="border-color: var(--vscode-border);">
                <div class="flex items-center mb-4">
                    <i class="fas fa-code text-blue-500 mr-2"></i>
                    <span class="font-semibold text-sm lg:text-base">ZERO ONE LABS</span>
                </div>
                <div class="text-xs" style="color: var(--vscode-text-muted);">
                    Admin Workspace
                </div>
            </div>

            <nav class="flex-1 p-2">
                <div class="space-y-1">
                    <a href="#" class="nav-item active flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-tachometer-alt mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Dashboard</span>
                    </a>
                    <a href="admin/clients.php" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-users mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Clients</span>
                    </a>
                    <a href="admin/projects.php" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-project-diagram mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Projects</span>
                    </a>
                    <a href="admin/inquiries.php" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-envelope mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Inquiries</span>
                    </a>
                    <a href="admin/company-info.php" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-info-circle mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Company Info</span>
                    </a>
                    <a href="admin/services.php" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-cogs mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Services</span>
                    </a>
                    <a href="admin/portfolio.php" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-briefcase mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Portfolio</span>
                    </a>
                    <a href="#" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-chart-bar mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Analytics</span>
                    </a>
                    <a href="#" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-cog mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Settings</span>
                    </a>
                </div>

                <div class="mt-6 p-2 lg:p-3 rounded text-xs font-mono hidden lg:block" style="background: var(--vscode-input-bg); border: 1px solid var(--vscode-border);">
                    <div class="mb-2 font-semibold">System Status</div>
                    <div style="color: var(--vscode-text-muted);">
                        <div class="text-green-400 mb-1">✓ Server Online</div>
                        <div class="text-blue-400 mb-1">→ DB Connected</div>
                        <div class="text-yellow-400">⚡ SSL Active</div>
                    </div>
                </div>
            </nav>

            <div class="p-3 lg:p-4 border-t" style="border-color: var(--vscode-border);">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-6 h-6 lg:w-8 lg:h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                        </div>
                        <div class="ml-2 lg:ml-3 hidden lg:block">
                            <div class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                            <div class="text-xs" style="color: var(--vscode-text-muted);">Administrator</div>
                        </div>
                    </div>
                    <a href="?logout=1" class="text-red-500 hover:text-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt text-sm"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Breadcrumb -->
            <div class="breadcrumb px-6 py-3 flex items-center text-sm">
                <span>admin</span>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span>dashboard</span>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span class="font-medium">overview.tsx</span>
            </div>

            <!-- VS Code Inspired Toolbar -->
            <div class="vscode-toolbar">
                <div class="toolbar-group">
                    <button class="toolbar-button primary" onclick="openCreateProjectModal()">
                        <i class="fas fa-plus"></i>
                        <span class="hidden sm:inline">New Project</span>
                    </button>
                    <button class="toolbar-button" onclick="window.location.href='admin/clients.php'">
                        <i class="fas fa-user-plus"></i>
                        <span class="hidden sm:inline">Add Client</span>
                    </button>
                </div>

                <div class="toolbar-separator"></div>

                <div class="toolbar-group">
                    <button class="toolbar-button" onclick="window.location.href='admin/support.php'">
                        <i class="fas fa-headset"></i>
                        <span class="hidden sm:inline">Support</span>
                        <?php if ($stats['open_tickets'] > 0): ?>
                        <span class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full ml-1"><?php echo $stats['open_tickets']; ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="toolbar-button" onclick="window.location.href='admin/inquiries.php'">
                        <i class="fas fa-envelope"></i>
                        <span class="hidden sm:inline">Inquiries</span>
                        <?php if ($stats['new_inquiries'] > 0): ?>
                        <span class="bg-blue-500 text-white text-xs px-1.5 py-0.5 rounded-full ml-1"><?php echo $stats['new_inquiries']; ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <div class="toolbar-separator"></div>

                <div class="toolbar-group">
                    <button class="toolbar-button" onclick="downloadSystemReport()">
                        <i class="fas fa-download"></i>
                        <span class="hidden sm:inline">Export Report</span>
                    </button>
                    <button class="toolbar-button" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i>
                        <span class="hidden sm:inline">Refresh</span>
                    </button>
                </div>

                <div class="toolbar-separator"></div>

                <div class="toolbar-group">
                    <button class="toolbar-button" onclick="window.location.href='admin/company-info.php'">
                        <i class="fas fa-cog"></i>
                        <span class="hidden sm:inline">Settings</span>
                    </button>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <span class="text-xs" style="color: var(--vscode-text-muted);">
                        <i class="fas fa-circle text-green-500 mr-1"></i>Online
                    </span>
                    <span class="text-xs" style="color: var(--vscode-text-muted);">
                        <?php echo $stats['total_clients']; ?> clients
                    </span>
                </div>
            </div>

            <!-- Message Display -->
            <?php if ($message): ?>
            <div class="mx-6 mt-4">
                <div class="p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-yellow-100 border border-yellow-400 text-yellow-700'; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dashboard Content -->
            <div class="flex-1 p-4 lg:p-6 overflow-auto" style="background: var(--vscode-editor);">
                <!-- Stats Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 lg:gap-6 mb-6 lg:mb-8">
                    <div class="stat-card p-4 lg:p-6 rounded-lg">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="mb-2 lg:mb-0">
                                <p class="text-xs lg:text-sm font-medium" style="color: var(--vscode-text-muted);">Total Clients</p>
                                <p class="text-xl lg:text-3xl font-bold mt-1 lg:mt-2"><?php echo $stats['total_clients']; ?></p>
                            </div>
                            <div class="w-8 h-8 lg:w-12 lg:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-blue-600 text-sm lg:text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2 lg:mt-4 flex items-center text-xs lg:text-sm">
                            <span class="text-green-500">↗ 12%</span>
                            <span class="ml-2 hidden lg:inline" style="color: var(--vscode-text-muted);">from last month</span>
                        </div>
                    </div>

                    <div class="stat-card p-4 lg:p-6 rounded-lg">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="mb-2 lg:mb-0">
                                <p class="text-xs lg:text-sm font-medium" style="color: var(--vscode-text-muted);">Total Projects</p>
                                <p class="text-xl lg:text-3xl font-bold mt-1 lg:mt-2"><?php echo $stats['total_projects']; ?></p>
                            </div>
                            <div class="w-8 h-8 lg:w-12 lg:h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-project-diagram text-green-600 text-sm lg:text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2 lg:mt-4 flex items-center text-xs lg:text-sm">
                            <span class="text-green-500">↗ 8%</span>
                            <span class="ml-2 hidden lg:inline" style="color: var(--vscode-text-muted);">from last month</span>
                        </div>
                    </div>

                    <div class="stat-card p-4 lg:p-6 rounded-lg">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="mb-2 lg:mb-0">
                                <p class="text-xs lg:text-sm font-medium" style="color: var(--vscode-text-muted);">Active Projects</p>
                                <p class="text-xl lg:text-3xl font-bold mt-1 lg:mt-2"><?php echo $stats['active_projects']; ?></p>
                            </div>
                            <div class="w-8 h-8 lg:w-12 lg:h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-yellow-600 text-sm lg:text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2 lg:mt-4 flex items-center text-xs lg:text-sm">
                            <span class="text-yellow-500">→ 0%</span>
                            <span class="ml-2 hidden lg:inline" style="color: var(--vscode-text-muted);">no change</span>
                        </div>
                    </div>

                    <div class="stat-card p-4 lg:p-6 rounded-lg">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="mb-2 lg:mb-0">
                                <p class="text-xs lg:text-sm font-medium" style="color: var(--vscode-text-muted);">Pending</p>
                                <p class="text-xl lg:text-3xl font-bold mt-1 lg:mt-2"><?php echo $stats['pending_approvals']; ?></p>
                            </div>
                            <div class="w-8 h-8 lg:w-12 lg:h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600 text-sm lg:text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2 lg:mt-4 flex items-center text-xs lg:text-sm">
                            <span class="text-red-500">↗ 3</span>
                            <span class="ml-2 hidden lg:inline" style="color: var(--vscode-text-muted);">need attention</span>
                        </div>
                    </div>

                    <div class="stat-card p-4 lg:p-6 rounded-lg">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="mb-2 lg:mb-0">
                                <p class="text-xs lg:text-sm font-medium" style="color: var(--vscode-text-muted);">Revenue</p>
                                <p class="text-xl lg:text-3xl font-bold mt-1 lg:mt-2">$<?php echo number_format($stats['revenue'], 0); ?></p>
                            </div>
                            <div class="w-8 h-8 lg:w-12 lg:h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-purple-600 text-sm lg:text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2 lg:mt-4 flex items-center text-xs lg:text-sm">
                            <span class="text-green-500">↗ 15%</span>
                            <span class="ml-2 hidden lg:inline" style="color: var(--vscode-text-muted);">this quarter</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Projects with Actions -->
                <div class="vscode-card rounded-lg mb-6 lg:mb-8">
                    <div class="px-4 lg:px-6 py-3 lg:py-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--vscode-border);">
                        <h3 class="text-base lg:text-lg font-semibold">Recent Projects</h3>
                        <div class="flex items-center gap-2">
                            <button onclick="window.location.href='admin/projects.php'" class="vscode-button-secondary px-3 lg:px-4 py-2 rounded text-xs lg:text-sm">
                                <i class="fas fa-list mr-2"></i>View All
                            </button>
                            <button onclick="openCreateProjectModal()" class="vscode-button px-3 lg:px-4 py-2 rounded text-xs lg:text-sm">
                                <i class="fas fa-plus mr-2"></i>New Project
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead style="background: var(--vscode-sidebar);">
                                <tr>
                                    <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Project</th>
                                    <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell" style="color: var(--vscode-text-muted);">Client</th>
                                    <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Status</th>
                                    <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider hidden lg:table-cell" style="color: var(--vscode-text-muted);">Budget</th>
                                    <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider hidden lg:table-cell" style="color: var(--vscode-text-muted);">Created</th>
                                    <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="divide-color: var(--vscode-border);">
                                <?php foreach ($recent_projects as $project): ?>
                                <tr class="table-row-hover">
                                    <td class="px-3 lg:px-6 py-3 lg:py-4">
                                        <div class="text-xs lg:text-sm font-medium"><?php echo htmlspecialchars($project['project_name']); ?></div>
                                        <div class="text-xs hidden lg:block" style="color: var(--vscode-text-muted);"><?php echo htmlspecialchars(substr($project['description'], 0, 50)) . '...'; ?></div>
                                    </td>
                                    <td class="px-3 lg:px-6 py-3 lg:py-4 hidden md:table-cell">
                                        <div class="text-xs lg:text-sm"><?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?></div>
                                        <div class="text-xs hidden lg:block" style="color: var(--vscode-text-muted);"><?php echo htmlspecialchars($project['company']); ?></div>
                                    </td>
                                    <td class="px-3 lg:px-6 py-3 lg:py-4">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_project_status">
                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="text-xs px-2 py-1 rounded border" style="background: var(--vscode-input-bg); border-color: var(--vscode-border); color: var(--vscode-text);">
                                                <option value="pending" <?php echo $project['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="in_progress" <?php echo $project['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $project['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-3 lg:px-6 py-3 lg:py-4 text-xs lg:text-sm hidden lg:table-cell" style="color: var(--vscode-text-muted);">
                                        $<?php echo $project['budget'] ? number_format($project['budget'], 0) : 'TBD'; ?>
                                    </td>
                                    <td class="px-3 lg:px-6 py-3 lg:py-4 text-xs lg:text-sm hidden lg:table-cell" style="color: var(--vscode-text-muted);">
                                        <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                                    </td>
                                    <td class="px-3 lg:px-6 py-3 lg:py-4 text-xs lg:text-sm">
                                        <div class="flex space-x-1 lg:space-x-2">
                                            <button onclick="viewProject(<?php echo $project['id']; ?>)" class="text-blue-600 hover:text-blue-800 p-1" title="View">
                                                <i class="fas fa-eye text-xs"></i>
                                            </button>
                                            <button onclick="editProject(<?php echo $project['id']; ?>)" class="text-green-600 hover:text-green-800 p-1" title="Edit">
                                                <i class="fas fa-edit text-xs"></i>
                                            </button>
                                            <button onclick="downloadProjectReport(<?php echo $project['id']; ?>)" class="text-purple-600 hover:text-purple-800 p-1" title="Download Report">
                                                <i class="fas fa-download text-xs"></i>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this project?')">
                                                <input type="hidden" name="action" value="delete_project">
                                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800 p-1 hidden lg:inline-block" title="Delete">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pending User Approvals -->
                <?php if (!empty($pending_users)): ?>
                <div class="vscode-card rounded-lg mb-6 lg:mb-8">
                    <div class="px-4 lg:px-6 py-3 lg:py-4 border-b" style="border-color: var(--vscode-border);">
                        <h3 class="text-base lg:text-lg font-semibold">Pending User Approvals</h3>
                    </div>
                    <div class="divide-y" style="divide-color: var(--vscode-border);">
                        <?php foreach ($pending_users as $user): ?>
                        <div class="px-4 lg:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                <div class="text-sm" style="color: var(--vscode-text-muted);">
                                    <?php echo htmlspecialchars($user['email']); ?> • <?php echo htmlspecialchars($user['company'] ?: 'No company'); ?>
                                </div>
                                <div class="text-xs" style="color: var(--vscode-text-muted);">
                                    Registered: <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="viewUserDetails(<?php echo $user['id']; ?>)" class="text-blue-600 hover:text-blue-800 p-2" title="View Details">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="approve_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="vscode-button px-3 py-1 rounded text-xs">
                                        <i class="fas fa-check mr-1"></i>Approve
                                    </button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="reject_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="vscode-button-secondary px-3 py-1 rounded text-xs">
                                        <i class="fas fa-times mr-1"></i>Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions Grid and etcc .-->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                    <div class="vscode-card p-4 lg:p-6 rounded-lg">
                        <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Development</h3>
                        <div class="space-y-2 lg:space-y-3 text-xs lg:text-sm font-mono">
                            <div class="flex items-center justify-between">
                                <span>Git Status</span>
                                <span class="text-green-500">✓ Clean</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Build Status</span>
                                <span class="text-green-500">✓ Passing</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Tests</span>
                                <span class="text-green-500">✓ 98% Coverage</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Deployment</span>
                                <span class="text-blue-500">→ Production</span>
                            </div>
                        </div>
                    </div>

                    <div class="vscode-card p-4 lg:p-6 rounded-lg md:col-span-2 lg:col-span-1">
                        <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Security</h3>
                        <div class="space-y-2 lg:space-y-3 text-xs lg:text-sm">
                            <div class="flex items-center justify-between">
                                <span>SSL Certificate</span>
                                <span class="text-green-500"><i class="fas fa-shield-alt"></i> Valid</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Data Encryption</span>
                                <span class="text-green-500"><i class="fas fa-lock"></i> AES-256</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Access Control</span>
                                <span class="text-green-500"><i class="fas fa-user-shield"></i> Active</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Backup Status</span>
                                <span class="text-blue-500"><i class="fas fa-cloud"></i> Synced</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Bar -->
            <div class="status-bar flex items-center justify-between px-6">
                <div class="flex items-center space-x-4 text-xs">
                    <span><i class="fas fa-code-branch mr-1"></i>main</span>
                    <span><i class="fas fa-sync mr-1"></i>Synced</span>
                    <span><i class="fas fa-users mr-1"></i><?php echo $stats['total_clients']; ?> clients</span>
                </div>
                <div class="flex items-center space-x-4 text-xs">
                    <span>PHP 8.2</span>
                    <span>MySQL 8.0</span>
                    <span><?php echo date('H:i'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Project View Modal -->
    <div id="projectViewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Project Details</h3>
                <button onclick="closeProjectViewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="projectViewContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">User Details</h3>
                <button onclick="closeUserDetailsModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="userDetailsContent">
                <!-- Content will be loaded here -->
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

function viewProject(projectId) {
    fetch(`api/projects.php?id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            const content = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
                            <p class="text-lg font-semibold">${data.project_name}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                            <p>${data.first_name} ${data.last_name}</p>
                            <p class="text-sm text-gray-600">${data.company || 'No company'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(data.status)}">
                                ${data.status.charAt(0).toUpperCase() + data.status.slice(1).replace('_', ' ')}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Budget</label>
                            <p class="font-mono">$${data.budget ? Number(data.budget).toLocaleString() : 'Not specified'}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <p class="text-sm">${data.description}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                            <p class="font-mono">${new Date(data.created_at).toLocaleDateString()}</p>
                        </div>
                        ${data.github_repo ? `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Repository</label>
                            <a href="${data.github_repo}" target="_blank" class="text-blue-600 hover:underline">
                                <i class="fab fa-github mr-1"></i>View Repository
                            </a>
                        </div>
                        ` : ''}
                        ${data.deployment_url ? `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Live Site</label>
                            <a href="${data.deployment_url}" target="_blank" class="text-blue-600 hover:underline">
                                <i class="fas fa-external-link-alt mr-1"></i>View Site
                            </a>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t">
                    <h4 class="text-lg font-semibold mb-4">Quick Actions</h4>
                    <div class="flex space-x-3">
                        <button onclick="editProject(${data.id}); closeProjectViewModal();" class="vscode-button px-4 py-2 rounded text-sm">
                            <i class="fas fa-edit mr-2"></i>Edit Project
                        </button>
                        <button onclick="downloadProjectReport(${data.id})" class="vscode-button-secondary px-4 py-2 rounded text-sm">
                            <i class="fas fa-download mr-2"></i>Download Report
                        </button>
                        <a href="mailto:${data.email}" class="vscode-button-secondary px-4 py-2 rounded text-sm">
                            <i class="fas fa-envelope mr-2"></i>Email Client
                        </a>
                    </div>
                </div>
            `;
            
            document.getElementById('projectViewContent').innerHTML = content;
            document.getElementById('projectViewModal').classList.remove('hidden');
            document.getElementById('projectViewModal').classList.add('flex');
        })
        .catch(error => {
            alert('Error loading project data');
        });
}

function viewUserDetails(userId) {
    fetch(`api/users.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            const content = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <p class="text-lg font-semibold">${data.first_name} ${data.last_name}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <p class="font-mono">${data.username}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <p>${data.email}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <p>${data.phone || 'Not provided'}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                            <p>${data.company || 'Not specified'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${data.status === 'active' ? 'bg-green-100 text-green-800' : data.status === 'inactive' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'}">
                                ${data.status.charAt(0).toUpperCase() + data.status.slice(1)}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Registered</label>
                            <p class="font-mono">${new Date(data.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <p>${data.address || 'Not provided'}</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t">
                    <h4 class="text-lg font-semibold mb-4">Quick Actions</h4>
                    <div class="flex space-x-3">
                        <button onclick="window.location.href='admin/clients.php'" class="vscode-button px-4 py-2 rounded text-sm">
                            <i class="fas fa-edit mr-2"></i>Manage Client
                        </button>
                        <a href="mailto:${data.email}" class="vscode-button-secondary px-4 py-2 rounded text-sm">
                            <i class="fas fa-envelope mr-2"></i>Send Email
                        </a>
                    </div>
                </div>
            `;
            
            document.getElementById('userDetailsContent').innerHTML = content;
            document.getElementById('userDetailsModal').classList.remove('hidden');
            document.getElementById('userDetailsModal').classList.add('flex');
        })
        .catch(error => {
            alert('Error loading user data');
        });
}

function getStatusColor(status) {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

function closeProjectViewModal() {
    document.getElementById('projectViewModal').classList.add('hidden');
    document.getElementById('projectViewModal').classList.remove('flex');
}

function closeUserDetailsModal() {
    document.getElementById('userDetailsModal').classList.add('hidden');
    document.getElementById('userDetailsModal').classList.remove('flex');
}

function editProject(projectId) {
    window.location.href = `admin/projects.php?edit=${projectId}`;
}

function downloadProjectReport(projectId) {
    // Create a simple report download
    const link = document.createElement('a');
    link.href = `api/download-report.php?type=project&id=${projectId}`;
    link.download = `project-${projectId}-report.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadSystemReport() {
    // Create a system report download
    const link = document.createElement('a');
    link.href = 'api/download-report.php?type=system';
    link.download = `system-report-${new Date().toISOString().split('T')[0]}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function openCreateProjectModal() {
    window.location.href = 'admin/projects.php';
}

function refreshDashboard() {
    // Reload the page to refresh dashboard data
    window.location.reload();
}

// Close modals when clicking outside
document.getElementById('projectViewModal').addEventListener('click', function(e) {
    if (e.target === this) closeProjectViewModal();
});

document.getElementById('userDetailsModal').addEventListener('click', function(e) {
    if (e.target === this) closeUserDetailsModal();
});

// Load saved theme
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const body = document.body;
    const themeIcon = document.getElementById('theme-icon');
    
    body.setAttribute('data-theme', savedTheme);
    themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
});

// Add smooth transitions to all interactive elements
document.querySelectorAll('button, a').forEach(element => {
    element.style.transition = 'all 0.2s ease';
});
</script>
</body>
</html>

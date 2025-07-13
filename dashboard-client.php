<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
$auth->requireRole('client');

$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'request_project':
                $project_name = trim($_POST['project_name']);
                $description = trim($_POST['description']);
                $budget = $_POST['budget'];
                $github_repo = trim($_POST['github_repo']);
                
                if (!empty($project_name) && !empty($description)) {
                    $query = "INSERT INTO projects (client_id, project_name, description, budget, github_repo, status) VALUES (:client_id, :project_name, :description, :budget, :github_repo, 'pending')";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':client_id', $user_id);
                    $stmt->bindParam(':project_name', $project_name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':budget', $budget);
                    $stmt->bindParam(':github_repo', $github_repo);
                    
                    if ($stmt->execute()) {
                        $message = "Project request submitted successfully";
                        $message_type = "success";
                    } else {
                        $message = "Failed to submit project request";
                        $message_type = "error";
                    }
                } else {
                    $message = "Please fill in all required fields";
                    $message_type = "error";
                }
                break;
                
            case 'update_profile':
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $company = trim($_POST['company']);
                $phone = trim($_POST['phone']);
                
                $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, company = :company, phone = :phone WHERE id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':company', $company);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $message = "Profile updated successfully";
                    $message_type = "success";
                    $_SESSION['full_name'] = $first_name . ' ' . $last_name;
                } else {
                    $message = "Failed to update profile";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get user's projects
$query = "SELECT * FROM projects WHERE client_id = :user_id ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user profile
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate statistics
$stats = [
    'total_projects' => count($projects),
    'active_projects' => count(array_filter($projects, function($p) { return $p['status'] == 'in_progress'; })),
    'completed_projects' => count(array_filter($projects, function($p) { return $p['status'] == 'completed'; })),
    'pending_projects' => count(array_filter($projects, function($p) { return $p['status'] == 'pending'; }))
];

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
    <title>Client Dashboard - Zero One Labs</title>
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

        .vscode-input {
            background: var(--vscode-input-bg);
            border: 1px solid var(--vscode-input-border);
            color: var(--vscode-text);
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
                <i class="fas fa-project-diagram text-xs"></i>
            </div>
            <div class="w-6 h-6 flex items-center justify-center" style="color: var(--vscode-text-muted);">
                <i class="fas fa-user text-xs"></i>
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
                    Client Portal
                </div>
            </div>

            <nav class="flex-1 p-2">
                <div class="space-y-1">
                    <a href="#" class="nav-item active flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-tachometer-alt mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Dashboard</span>
                    </a>
                    <a href="#projects" onclick="showSection('projects')" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-project-diagram mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">My Projects</span>
                    </a>
                    <a href="#profile" onclick="showSection('profile')" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-user mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Profile</span>
                    </a>
                    <a href="#support" onclick="showSection('support')" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-headset mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Support</span>
                    </a>
                    <a href="#messages" onclick="showSection('messages')" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-comments mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Messages</span>
                    </a>
                    <a href="#" class="nav-item flex items-center px-2 lg:px-3 py-2 text-xs lg:text-sm rounded">
                        <i class="fas fa-file-alt mr-2 lg:mr-3 text-xs"></i>
                        <span class="hidden lg:inline">Documentation</span>
                    </a>
                </div>

                <div class="mt-6 p-2 lg:p-3 rounded text-xs font-mono hidden lg:block" style="background: var(--vscode-input-bg); border: 1px solid var(--vscode-border);">
                    <div class="mb-2 font-semibold">Project Status</div>
                    <div style="color: var(--vscode-text-muted);">
                        <div class="text-green-400 mb-1">✓ <?php echo $stats['completed_projects']; ?> Completed</div>
                        <div class="text-blue-400 mb-1">→ <?php echo $stats['active_projects']; ?> Active</div>
                        <div class="text-yellow-400">⏳ <?php echo $stats['pending_projects']; ?> Pending</div>
                    </div>
                </div>
            </nav>

            <div class="p-3 lg:p-4 border-t" style="border-color: var(--vscode-border);">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-6 h-6 lg:w-8 lg:h-8 bg-green-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                        </div>
                        <div class="ml-2 lg:ml-3 hidden lg:block">
                            <div class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                            <div class="text-xs" style="color: var(--vscode-text-muted);">Client</div>
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
                <span>client</span>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span>dashboard</span>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span class="font-medium">overview.tsx</span>
            </div>

            <!-- Message Display -->
            <?php if ($message): ?>
            <div class="mx-6 mt-4">
                <div class="p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dashboard Content -->
            <div class="flex-1 p-4 lg:p-6 overflow-auto" style="background: var(--vscode-editor);">
                <!-- Dashboard Section -->
                <div id="dashboard-section">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6 mb-6 lg:mb-8">
                        <div class="stat-card p-4 lg:p-6 rounded-lg">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="mb-2 lg:mb-0">
                                    <p class="text-xs lg:text-sm font-medium" style="color: var(--vscode-text-muted);">Total Projects</p>
                                    <p class="text-xl lg:text-3xl font-bold mt-1 lg:mt-2"><?php echo $stats['total_projects']; ?></p>
                                </div>
                                <div class="w-8 h-8 lg:w-12 lg:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-project-diagram text-blue-600 text-sm lg:text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card p-4 lg:p-6 rounded-lg">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="mb-2 lg:mb-0">
                                    <p class="text-xs lg:text-sm font-medium" style="color: var(--vscode-text-muted);">Active</p>
                                    <p class="text-xl lg:text-3xl font-bold mt-1 lg:mt-2"><?php echo $stats['active_projects']; ?></p>
                                </div>
                                <div class="w-8 h-8 lg:w-12 lg:h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-play text-green-600 text-sm lg:text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card p-4 lg:p-6 rounded-lg">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="mb-2 lg:mb-0">
                                    <p class="text-xs lg:text-sm font-medium" style="color: var(--vscode-text-muted);">Completed</p>
                                    <p class="text-xl lg:text-3xl font-bold mt-1 lg:mt-2"><?php echo $stats['completed_projects']; ?></p>
                                </div>
                                <div class="w-8 h-8 lg:w-12 lg:h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-check text-purple-600 text-sm lg:text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card p-4 lg:p-6 rounded-lg">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="mb-2 lg:mb-0">
                                    <p class="text-xs lg:text-sm font-medium" style="color: var(--vscode-text-muted);">Pending</p>
                                    <p class="text-xl lg:text-3xl font-bold mt-1 lg:mt-2"><?php echo $stats['pending_projects']; ?></p>
                                </div>
                                <div class="w-8 h-8 lg:w-12 lg:h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-clock text-yellow-600 text-sm lg:text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Projects -->
                    <div class="vscode-card rounded-lg mb-6 lg:mb-8">
                        <div class="px-4 lg:px-6 py-3 lg:py-4 border-b flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" style="border-color: var(--vscode-border);">
                            <h3 class="text-base lg:text-lg font-semibold">My Projects</h3>
                            <button onclick="openNewProjectModal()" class="vscode-button px-3 lg:px-4 py-2 rounded text-xs lg:text-sm">
                                <i class="fas fa-plus mr-2"></i>Request New Project
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead style="background: var(--vscode-sidebar);">
                                    <tr>
                                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Project</th>
                                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Status</th>
                                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider hidden lg:table-cell" style="color: var(--vscode-text-muted);">Budget</th>
                                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider hidden lg:table-cell" style="color: var(--vscode-text-muted);">Created</th>
                                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" style="divide-color: var(--vscode-border);">
                                    <?php if (empty($projects)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center" style="color: var(--vscode-text-muted);">
                                            <i class="fas fa-project-diagram text-4xl mb-4 opacity-50"></i>
                                            <p class="text-lg mb-2">No projects yet</p>
                                            <p class="text-sm">Request your first project to get started</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($projects as $project): ?>
                                    <tr class="hover:bg-opacity-50" style="hover:background-color: var(--vscode-sidebar);">
                                        <td class="px-3 lg:px-6 py-3 lg:py-4">
                                            <div class="text-xs lg:text-sm font-medium"><?php echo htmlspecialchars($project['project_name']); ?></div>
                                            <div class="text-xs hidden lg:block" style="color: var(--vscode-text-muted);"><?php echo htmlspecialchars(substr($project['description'], 0, 50)) . '...'; ?></div>
                                        </td>
                                        <td class="px-3 lg:px-6 py-3 lg:py-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusColor($project['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 lg:px-6 py-3 lg:py-4 text-xs lg:text-sm hidden lg:table-cell" style="color: var(--vscode-text-muted);">
                                            $<?php echo $project['budget'] ? number_format($project['budget'], 0) : 'TBD'; ?>
                                        </td>
                                        <td class="px-3 lg:px-6 py-3 lg:py-4 text-xs lg:text-sm hidden lg:table-cell" style="color: var(--vscode-text-muted);">
                                            <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                                        </td>
                                        <td class="px-3 lg:px-6 py-3 lg:py-4 text-xs lg:text-sm">
                                            <div class="flex space-x-1 lg:space-x-2">
                                                <button onclick="viewProject(<?php echo $project['id']; ?>)" class="text-blue-600 hover:text-blue-800 p-1" title="View Details">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </button>
                                                <button onclick="downloadProjectFiles(<?php echo $project['id']; ?>)" class="text-green-600 hover:text-green-800 p-1" title="Download Files">
                                                    <i class="fas fa-download text-xs"></i>
                                                </button>
                                                <?php if ($project['github_repo']): ?>
                                                <a href="<?php echo htmlspecialchars($project['github_repo']); ?>" target="_blank" class="text-purple-600 hover:text-purple-800 p-1" title="View Repository">
                                                    <i class="fab fa-github text-xs"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($project['deployment_url']): ?>
                                                <a href="<?php echo htmlspecialchars($project['deployment_url']); ?>" target="_blank" class="text-orange-600 hover:text-orange-800 p-1" title="View Live Site">
                                                    <i class="fas fa-external-link-alt text-xs"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                        <div class="vscode-card p-4 lg:p-6 rounded-lg">
                            <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Quick Actions</h3>
                            <div class="space-y-2 lg:space-y-3">
                                <button onclick="openNewProjectModal()" class="vscode-button w-full px-3 lg:px-4 py-2 rounded text-xs lg:text-sm text-left">
                                    <i class="fas fa-plus mr-2"></i>Request New Project
                                </button>
                                <button onclick="showSection('profile')" class="vscode-button w-full px-3 lg:px-4 py-2 rounded text-xs lg:text-sm text-left">
                                    <i class="fas fa-user mr-2"></i>Update Profile
                                </button>
                                <button onclick="showSection('support')" class="vscode-button w-full px-3 lg:px-4 py-2 rounded text-xs lg:text-sm text-left">
                                    <i class="fas fa-headset mr-2"></i>Contact Support
                                </button>
                                <button onclick="showSection('messages')" class="vscode-button w-full px-3 lg:px-4 py-2 rounded text-xs lg:text-sm text-left">
                                    <i class="fas fa-comments mr-2"></i>View Messages
                                </button>
                                <button onclick="downloadAllProjects()" class="vscode-button-secondary w-full px-3 lg:px-4 py-2 rounded text-xs lg:text-sm text-left">
                                    <i class="fas fa-download mr-2"></i>Download All Projects
                                </button>
                            </div>
                        </div>

                        <div class="vscode-card p-4 lg:p-6 rounded-lg">
                            <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Resources</h3>
                            <div class="space-y-2 lg:space-y-3 text-xs lg:text-sm">
                                <a href="#" class="flex items-center justify-between hover:text-blue-600">
                                    <span><i class="fas fa-book mr-2"></i>Documentation</span>
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                                <a href="#" class="flex items-center justify-between hover:text-blue-600">
                                    <span><i class="fas fa-video mr-2"></i>Video Tutorials</span>
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                                <a href="#" class="flex items-center justify-between hover:text-blue-600">
                                    <span><i class="fas fa-question-circle mr-2"></i>FAQ</span>
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                                <a href="#" class="flex items-center justify-between hover:text-blue-600">
                                    <span><i class="fas fa-comments mr-2"></i>Community</span>
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                            </div>
                        </div>

                        <div class="vscode-card p-4 lg:p-6 rounded-lg md:col-span-2 lg:col-span-1">
                            <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4">Account Status</h3>
                            <div class="space-y-2 lg:space-y-3 text-xs lg:text-sm">
                                <div class="flex items-center justify-between">
                                    <span>Account Status</span>
                                    <span class="text-green-500"><i class="fas fa-check-circle"></i> Active</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Member Since</span>
                                    <span class="font-mono"><?php echo date('M Y', strtotime($user_profile['created_at'])); ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Support Level</span>
                                    <span class="text-blue-500"><i class="fas fa-star"></i> Premium</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Next Billing</span>
                                    <span class="text-gray-500">N/A</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projects Section -->
                <div id="projects-section" class="hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">My Projects</h2>
                        <button onclick="openNewProjectModal()" class="vscode-button px-4 py-2 rounded">
                            <i class="fas fa-plus mr-2"></i>Request New Project
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($projects as $project): ?>
                        <div class="vscode-card rounded-lg p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusColor($project['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                </span>
                            </div>
                            <p class="text-sm mb-4" style="color: var(--vscode-text-muted);">
                                <?php echo htmlspecialchars(substr($project['description'], 0, 100)) . '...'; ?>
                            </p>
                            <div class="flex justify-between items-center text-sm mb-4" style="color: var(--vscode-text-muted);">
                                <span>Budget: $<?php echo $project['budget'] ? number_format($project['budget'], 0) : 'TBD'; ?></span>
                                <span><?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="viewProject(<?php echo $project['id']; ?>)" class="vscode-button flex-1 px-3 py-2 rounded text-xs">
                                    <i class="fas fa-eye mr-1"></i>View
                                </button>
                                <button onclick="downloadProjectFiles(<?php echo $project['id']; ?>)" class="vscode-button-secondary px-3 py-2 rounded text-xs">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Profile Section -->
                <div id="profile-section" class="hidden">
                    <h2 class="text-2xl font-bold mb-6">Profile Settings</h2>
                    
                    <div class="vscode-card rounded-lg p-6">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium mb-2">First Name</label>
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_profile['first_name']); ?>" required class="vscode-input w-full px-3 py-2 rounded">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Last Name</label>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_profile['last_name']); ?>" required class="vscode-input w-full px-3 py-2 rounded">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Email</label>
                                    <input type="email" value="<?php echo htmlspecialchars($user_profile['email']); ?>" disabled class="vscode-input w-full px-3 py-2 rounded bg-gray-100">
                                    <p class="text-xs mt-1" style="color: var(--vscode-text-muted);">Email cannot be changed</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Phone</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_profile['phone']); ?>" class="vscode-input w-full px-3 py-2 rounded">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-2">Company</label>
                                    <input type="text" name="company" value="<?php echo htmlspecialchars($user_profile['company']); ?>" class="vscode-input w-full px-3 py-2 rounded">
                                </div>
                            </div>
                            <div class="mt-6">
                                <button type="submit" class="vscode-button px-6 py-2 rounded">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Messages Section (Chat-like interface for inquiries and support) -->
                <div id="messages-section" class="hidden">
                    <h2 class="text-2xl font-bold mb-6">Messages & Conversations</h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Conversations List -->
                        <div class="lg:col-span-1">
                            <div class="vscode-card rounded-lg p-4">
                                <h3 class="text-lg font-semibold mb-4">Conversations</h3>
                                <div class="space-y-2" id="conversationsList">
                                    <!-- Conversations will be loaded here -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chat Interface -->
                        <div class="lg:col-span-2">
                            <div class="vscode-card rounded-lg h-96 flex flex-col">
                                <div class="p-4 border-b flex justify-between items-center" style="border-color: var(--vscode-border);">
                                    <h3 class="text-lg font-semibold" id="chatTitle">Select a conversation</h3>
                                    <button onclick="loadConversations()" class="vscode-button-secondary px-3 py-1 rounded text-sm">
                                        <i class="fas fa-refresh mr-1"></i>Refresh
                                    </button>
                                </div>
                                <div class="flex-1 overflow-y-auto p-4" id="chatMessages">
                                    <div class="text-center text-gray-500 mt-8">
                                        <i class="fas fa-comments text-4xl mb-4 opacity-50"></i>
                                        <p>Select a conversation to start messaging</p>
                                    </div>
                                </div>
                                <div class="p-4 border-t" style="border-color: var(--vscode-border);" id="chatInput" style="display: none;">
                                    <form onsubmit="sendMessage(event)" class="flex space-x-2">
                                        <input type="text" id="messageInput" placeholder="Type your message..." class="vscode-input flex-1 px-3 py-2 rounded" required>
                                        <button type="submit" class="vscode-button px-4 py-2 rounded">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Section -->
                <div id="support-section" class="hidden">
                    <h2 class="text-2xl font-bold mb-6">Support Center</h2>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="vscode-card rounded-lg p-6">
                            <h3 class="text-lg font-semibold mb-4">Contact Support</h3>
                            <form id="supportForm" onsubmit="submitSupportTicket(event)">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-2">Subject</label>
                                    <input type="text" name="subject" required class="vscode-input w-full px-3 py-2 rounded" placeholder="Brief description of your issue">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-2">Priority</label>
                                    <select name="priority" class="vscode-input w-full px-3 py-2 rounded">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-2">Message</label>
                                    <textarea name="message" rows="4" required class="vscode-input w-full px-3 py-2 rounded" placeholder="Describe your issue in detail..."></textarea>
                                </div>
                                <button type="submit" class="vscode-button px-4 py-2 rounded">
                                    <i class="fas fa-paper-plane mr-2"></i>Send Message
                                </button>
                            </form>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="vscode-card rounded-lg p-6">
                                <h3 class="text-lg font-semibold mb-4">My Support Tickets</h3>
                                <div id="supportTicketsList" class="space-y-3">
                                    <!-- Tickets will be loaded here -->
                                </div>
                                <button onclick="loadSupportTickets()" class="vscode-button-secondary w-full mt-4 px-3 py-2 rounded text-sm">
                                    <i class="fas fa-refresh mr-2"></i>Refresh Tickets
                                </button>
                            </div>
                            
                            <div class="vscode-card rounded-lg p-6">
                                <h3 class="text-lg font-semibold mb-4">Quick Help</h3>
                                <div class="space-y-3">
                                    <a href="#" class="flex items-center justify-between hover:text-blue-600">
                                        <span><i class="fas fa-question-circle mr-2"></i>Frequently Asked Questions</span>
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </a>
                                    <a href="#" class="flex items-center justify-between hover:text-blue-600">
                                        <span><i class="fas fa-book mr-2"></i>User Guide</span>
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </a>
                                    <a href="#" class="flex items-center justify-between hover:text-blue-600">
                                        <span><i class="fas fa-video mr-2"></i>Video Tutorials</span>
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </a>
                                    <a href="#" class="flex items-center justify-between hover:text-blue-600">
                                        <span><i class="fas fa-comments mr-2"></i>Community Forum</span>
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="vscode-card rounded-lg p-6">
                                <h3 class="text-lg font-semibold mb-4">Contact Information</h3>
                                <div class="space-y-3 text-sm">
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope mr-3 text-blue-600"></i>
                                        <span>support@zeroonelabs.com</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone mr-3 text-green-600"></i>
                                        <span>+1 (555) 123-4567</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-clock mr-3 text-purple-600"></i>
                                        <span>Mon-Fri, 9AM-6PM EST</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Bar -->
            <div class="status-bar flex items-center justify-between px-6">
                <div class="flex items-center space-x-4 text-xs">
                    <span><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span><i class="fas fa-project-diagram mr-1"></i><?php echo $stats['total_projects']; ?> projects</span>
                    <span><i class="fas fa-check mr-1"></i><?php echo $stats['completed_projects']; ?> completed</span>
                </div>
                <div class="flex items-center space-x-4 text-xs">
                    <span>Client Portal</span>
                    <span><?php echo date('H:i'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- New Project Modal -->
    <div id="newProjectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Request New Project</h3>
                <button onclick="closeNewProjectModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="request_project">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Project Name *</label>
                        <input type="text" name="project_name" required class="vscode-input w-full px-3 py-2 rounded" placeholder="Enter project name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Description *</label>
                        <textarea name="description" rows="4" required class="vscode-input w-full px-3 py-2 rounded" placeholder="Describe your project requirements..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Budget (USD)</label>
                        <input type="number" name="budget" class="vscode-input w-full px-3 py-2 rounded" placeholder="Enter estimated budget">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">GitHub Repository (Optional)</label>
                        <input type="url" name="github_repo" class="vscode-input w-full px-3 py-2 rounded" placeholder="https://github.com/username/repo">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeNewProjectModal()" class="vscode-button-secondary px-4 py-2 rounded">
                        Cancel
                    </button>
                    <button type="submit" class="vscode-button px-4 py-2 rounded">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Request
                    </button>
                </div>
            </form>
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

    <script>
<?php
function getStatusColor($status) {
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800'
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-800';
}
?>

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

function showSection(sectionName) {
    // Hide all sections
    document.getElementById('dashboard-section').classList.add('hidden');
    document.getElementById('projects-section').classList.add('hidden');
    document.getElementById('profile-section').classList.add('hidden');
    document.getElementById('support-section').classList.add('hidden');
    document.getElementById('messages-section').classList.add('hidden'); // Added this line
    
    // Show selected section
    document.getElementById(sectionName + '-section').classList.remove('hidden');
    
    // Update navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    if (sectionName === 'dashboard') {
        document.querySelector('.nav-item').classList.add('active');
    }
    
    // Load data for specific sections
    if (sectionName === 'support') {
        loadSupportTickets();
    }
    
    if (sectionName === 'messages') {
        loadConversations();
    }
}

function openNewProjectModal() {
    document.getElementById('newProjectModal').classList.remove('hidden');
    document.getElementById('newProjectModal').classList.add('flex');
}

function closeNewProjectModal() {
    document.getElementById('newProjectModal').classList.add('hidden');
    document.getElementById('newProjectModal').classList.remove('flex');
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${getStatusColorJS(data.status)}">
                                ${data.status.charAt(0).toUpperCase() + data.status.slice(1).replace('_', ' ')}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Budget</label>
                            <p class="font-mono">$${data.budget ? Number(data.budget).toLocaleString() : 'Not specified'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                            <p class="font-mono">${new Date(data.created_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <p class="text-sm">${data.description}</p>
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
                    <h4 class="text-lg font-semibold mb-4">Project Files & Resources</h4>
                    <div class="flex space-x-3">
                        <button onclick="downloadProjectFiles(${data.id})" class="vscode-button px-4 py-2 rounded text-sm">
                            <i class="fas fa-download mr-2"></i>Download Files
                        </button>
                        ${data.github_repo ? `
                        <a href="${data.github_repo}" target="_blank" class="vscode-button-secondary px-4 py-2 rounded text-sm">
                            <i class="fab fa-github mr-2"></i>View Code
                        </a>
                        ` : ''}
                        ${data.deployment_url ? `
                        <a href="${data.deployment_url}" target="_blank" class="vscode-button-secondary px-4 py-2 rounded text-sm">
                            <i class="fas fa-external-link-alt mr-2"></i>View Live Site
                        </a>
                        ` : ''}
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

function getStatusColorJS(status) {
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

function downloadProjectFiles(projectId) {
    // Create a download link for project files
    const link = document.createElement('a');
    link.href = `api/download-project.php?id=${projectId}`;
    link.download = `project-${projectId}-files.zip`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadAllProjects() {
    // Create a download link for all projects
    const link = document.createElement('a');
    link.href = 'api/download-all-projects.php';
    link.download = `all-projects-${new Date().toISOString().split('T')[0]}.zip`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function submitSupportTicket(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const ticketData = {
        subject: formData.get('subject'),
        message: formData.get('message'),
        priority: formData.get('priority')
    };
    
    fetch('api/support.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(ticketData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Support ticket submitted successfully!');
            form.reset();
            loadSupportTickets(); // Refresh the tickets list
        } else {
            alert('Error: ' + (data.error || 'Failed to submit ticket'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting support ticket');
    });
}

function loadSupportTickets() {
    fetch('api/support.php')
    .then(response => response.json())
    .then(data => {
        const ticketsList = document.getElementById('supportTicketsList');
        
        if (data.tickets && data.tickets.length > 0) {
            ticketsList.innerHTML = data.tickets.map(ticket => `
                <div class="border rounded-lg p-3" style="border-color: var(--vscode-border);">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-medium text-sm">${ticket.subject}</h4>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${getTicketStatusColor(ticket.status)}">
                            ${ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1).replace('_', ' ')}
                        </span>
                    </div>
                    <p class="text-xs mb-2" style="color: var(--vscode-text-muted);">${ticket.message.substring(0, 100)}${ticket.message.length > 100 ? '...' : ''}</p>
                    <div class="flex justify-between items-center text-xs" style="color: var(--vscode-text-muted);">
                        <span>Priority: ${ticket.priority}</span>
                        <span>${new Date(ticket.created_at).toLocaleDateString()}</span>
                    </div>
                    ${ticket.admin_response ? `
                    <div class="mt-2 p-2 bg-blue-50 rounded text-xs" style="background: var(--vscode-sidebar);">
                        <strong>Response:</strong> ${ticket.admin_response}
                    </div>
                    ` : ''}
                </div>
            `).join('');
        } else {
            ticketsList.innerHTML = '<p class="text-sm" style="color: var(--vscode-text-muted);">No support tickets yet</p>';
        }
    })
    .catch(error => {
        console.error('Error loading tickets:', error);
        document.getElementById('supportTicketsList').innerHTML = '<p class="text-sm text-red-500">Error loading tickets</p>';
    });
}

function getTicketStatusColor(status) {
    const colors = {
        'open': 'bg-yellow-100 text-yellow-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'resolved': 'bg-green-100 text-green-800',
        'closed': 'bg-gray-100 text-gray-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

// Chat-like interface functions
let currentConversation = null;
let conversations = [];

function loadConversations() {
    // Load both support tickets and inquiries
    Promise.all([
        fetch('api/support.php').then(response => response.json()),
        fetch('api/inquiries.php?user_id=' + <?php echo $user_id; ?>).then(response => response.json()).catch(() => ({ inquiries: [] }))
    ])
    .then(([supportData, inquiriesData]) => {
        conversations = [];
        
        // Add support tickets
        if (supportData.tickets) {
            supportData.tickets.forEach(ticket => {
                conversations.push({
                    id: 'support_' + ticket.id,
                    type: 'support',
                    title: ticket.subject,
                    status: ticket.status,
                    lastMessage: ticket.message.substring(0, 50) + '...',
                    timestamp: ticket.created_at,
                    data: ticket
                });
            });
        }
        
        // Add inquiries (if user has any)
        if (inquiriesData.inquiries) {
            inquiriesData.inquiries.forEach(inquiry => {
                conversations.push({
                    id: 'inquiry_' + inquiry.id,
                    type: 'inquiry',
                    title: inquiry.subject || 'General Inquiry',
                    status: inquiry.status,
                    lastMessage: inquiry.message.substring(0, 50) + '...',
                    timestamp: inquiry.created_at,
                    data: inquiry
                });
            });
        }
        
        // Sort by timestamp (newest first)
        conversations.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
        
        displayConversations();
    })
    .catch(error => {
        console.error('Error loading conversations:', error);
    });
}

function displayConversations() {
    const conversationsList = document.getElementById('conversationsList');
    
    if (conversations.length === 0) {
        conversationsList.innerHTML = `
            <div class="text-center text-gray-500 py-4">
                <i class="fas fa-comments text-2xl mb-2 opacity-50"></i>
                <p class="text-sm">No conversations yet</p>
            </div>
        `;
        return;
    }
    
    conversationsList.innerHTML = conversations.map(conv => `
        <div class="conversation-item p-3 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors ${currentConversation?.id === conv.id ? 'bg-blue-50 border border-blue-200' : ''}" 
             onclick="selectConversation('${conv.id}')">
            <div class="flex justify-between items-start mb-1">
                <h4 class="font-medium text-sm">${conv.title}</h4>
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${getConversationStatusColor(conv.status)}">
                    ${conv.status.charAt(0).toUpperCase() + conv.status.slice(1).replace('_', ' ')}
                </span>
            </div>
            <p class="text-xs text-gray-600 mb-2">${conv.lastMessage}</p>
            <div class="flex justify-between items-center text-xs text-gray-500">
                <span>${conv.type === 'support' ? 'Support Ticket' : 'Inquiry'}</span>
                <span>${formatTimestamp(conv.timestamp)}</span>
            </div>
        </div>
    `).join('');
}

function selectConversation(conversationId) {
    currentConversation = conversations.find(c => c.id === conversationId);
    if (!currentConversation) return;
    
    // Update UI
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('bg-blue-50', 'border', 'border-blue-200');
    });
    event.target.closest('.conversation-item').classList.add('bg-blue-50', 'border', 'border-blue-200');
    
    // Update chat title
    document.getElementById('chatTitle').textContent = currentConversation.title;
    
    // Show chat input
    document.getElementById('chatInput').style.display = 'block';
    
    // Load messages
    loadConversationMessages();
}

function loadConversationMessages() {
    const chatMessages = document.getElementById('chatMessages');
    const conversation = currentConversation.data;
    
    // Extract conversation ID and type
    const conversationId = currentConversation.id.split('_')[1];
    const conversationType = currentConversation.id.split('_')[0];
    
    // Load messages from API
    fetch(`api/messages.php?conversation_id=${conversationId}&conversation_type=${conversationType}`)
    .then(response => response.json())
    .then(data => {
        if (data.messages && data.messages.length > 0) {
            // Display messages
            chatMessages.innerHTML = data.messages.map(msg => `
                <div class="mb-4 ${msg.sender_type === 'client' ? 'text-right' : 'text-left'}">
                    <div class="inline-block max-w-xs lg:max-w-md p-3 rounded-lg ${msg.sender_type === 'client' ? 'bg-blue-600 text-white' : 'bg-gray-100'}">
                        <p class="text-sm">${msg.message}</p>
                        <p class="text-xs mt-1 opacity-70">${formatTimestamp(msg.timestamp)}</p>
                    </div>
                </div>
            `).join('');
        } else {
            // Fallback to original method if API fails
            let messages = [];
            
            // Add client's initial message
            messages.push({
                sender: 'client',
                message: conversation.message,
                timestamp: conversation.created_at
            });
            
            // Add admin response if exists
            if (conversation.admin_response) {
                messages.push({
                    sender: 'admin',
                    message: conversation.admin_response,
                    timestamp: conversation.responded_at || conversation.updated_at
                });
            }
            
            // Display messages
            chatMessages.innerHTML = messages.map(msg => `
                <div class="mb-4 ${msg.sender_type === 'client' ? 'text-right' : 'text-left'}">
                    <div class="inline-block max-w-xs lg:max-w-md p-3 rounded-lg ${msg.sender_type === 'client' ? 'bg-blue-600 text-white' : 'bg-gray-100'}">
                        <p class="text-sm">${msg.message}</p>
                        <p class="text-xs mt-1 opacity-70">${formatTimestamp(msg.timestamp)}</p>
                    </div>
                </div>
            `).join('');
        }
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    })
    .catch(error => {
        console.error('Error loading messages:', error);
        
        // Fallback to original method
        let messages = [];
        
        // Add client's initial message
        messages.push({
            sender: 'client',
            message: conversation.message,
            timestamp: conversation.created_at
        });
        
        // Add admin response if exists
        if (conversation.admin_response) {
            messages.push({
                sender: 'admin',
                message: conversation.admin_response,
                timestamp: conversation.responded_at || conversation.updated_at
            });
        }
        
        // Display messages
        chatMessages.innerHTML = messages.map(msg => `
            <div class="mb-4 ${msg.sender_type === 'client' ? 'text-right' : 'text-left'}">
                <div class="inline-block max-w-xs lg:max-w-md p-3 rounded-lg ${msg.sender_type === 'client' ? 'bg-blue-600 text-white' : 'bg-gray-100'}">
                    <p class="text-sm">${msg.message}</p>
                    <p class="text-xs mt-1 opacity-70">${formatTimestamp(msg.timestamp)}</p>
                </div>
            </div>
        `).join('');
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
}

function sendMessage(event) {
    event.preventDefault();
    
    if (!currentConversation) return;
    
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    // Add message to chat immediately (optimistic update)
    const chatMessages = document.getElementById('chatMessages');
    const messageHtml = `
        <div class="mb-4 text-right">
            <div class="inline-block max-w-xs lg:max-w-md p-3 rounded-lg bg-blue-600 text-white">
                <p class="text-sm">${message}</p>
                <p class="text-xs mt-1 opacity-70">${formatTimestamp(new Date().toISOString())}</p>
            </div>
        </div>
    `;
    chatMessages.insertAdjacentHTML('beforeend', messageHtml);
    
    // Clear input
    messageInput.value = '';
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Extract conversation ID and type
    const conversationId = currentConversation.id.split('_')[1];
    const conversationType = currentConversation.id.split('_')[0];
    
    // Send message to server
    fetch('api/messages.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            conversation_id: conversationId,
            conversation_type: conversationType,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Message sent successfully
            console.log('Message sent:', data.message);
            
            // Update conversation in the list
            const conversationItem = conversations.find(c => c.id === currentConversation.id);
            if (conversationItem) {
                conversationItem.lastMessage = message.substring(0, 50) + '...';
                conversationItem.timestamp = new Date().toISOString();
                displayConversations();
            }
        } else {
            // Show error message
            alert('Error: ' + (data.error || 'Failed to send message'));
            
            // Remove the optimistic message
            const lastMessage = chatMessages.lastElementChild;
            if (lastMessage) {
                lastMessage.remove();
            }
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        alert('Error sending message. Please try again.');
        
        // Remove the optimistic message
        const lastMessage = chatMessages.lastElementChild;
        if (lastMessage) {
            lastMessage.remove();
        }
    });
}

function getConversationStatusColor(status) {
    const colors = {
        'new': 'bg-blue-100 text-blue-800',
        'in_review': 'bg-yellow-100 text-yellow-800',
        'responded': 'bg-green-100 text-green-800',
        'closed': 'bg-gray-100 text-gray-800',
        'open': 'bg-yellow-100 text-yellow-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'resolved': 'bg-green-100 text-green-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffInHours = (now - date) / (1000 * 60 * 60);
    
    if (diffInHours < 24) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } else if (diffInHours < 48) {
        return 'Yesterday';
    } else {
        return date.toLocaleDateString();
    }
}

// Close modals when clicking outside
document.getElementById('newProjectModal').addEventListener('click', function(e) {
    if (e.target === this) closeNewProjectModal();
});

document.getElementById('projectViewModal').addEventListener('click', function(e) {
    if (e.target === this) closeProjectViewModal();
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

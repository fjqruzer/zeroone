<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$database = new Database();
$conn = $database->getConnection();

// Handle CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $client_id = $_POST['client_id'];
                $project_name = trim($_POST['project_name']);
                $description = trim($_POST['description']);
                $requirements = trim($_POST['requirements']);
                $budget = $_POST['budget'];
                $estimated_completion = $_POST['estimated_completion'];
                $github_repo = trim($_POST['github_repo']);
                $deployment_url = trim($_POST['deployment_url']);
                
                $query = "INSERT INTO projects (client_id, project_name, description, requirements, budget, estimated_completion, github_repo, deployment_url, status) 
                         VALUES (:client_id, :project_name, :description, :requirements, :budget, :estimated_completion, :github_repo, :deployment_url, 'pending')";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':client_id', $client_id);
                $stmt->bindParam(':project_name', $project_name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':requirements', $requirements);
                $stmt->bindParam(':budget', $budget);
                $stmt->bindParam(':estimated_completion', $estimated_completion);
                $stmt->bindParam(':github_repo', $github_repo);
                $stmt->bindParam(':deployment_url', $deployment_url);
                
                if ($stmt->execute()) {
                    $message = "Project created successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to create project";
                    $message_type = "error";
                }
                break;
                
            case 'update':
                $project_id = $_POST['project_id'];
                $project_name = trim($_POST['project_name']);
                $description = trim($_POST['description']);
                $requirements = trim($_POST['requirements']);
                $budget = $_POST['budget'];
                $estimated_completion = $_POST['estimated_completion'];
                $github_repo = trim($_POST['github_repo']);
                $deployment_url = trim($_POST['deployment_url']);
                $admin_notes = trim($_POST['admin_notes']);
                
                $query = "UPDATE projects SET project_name = :project_name, description = :description, requirements = :requirements, 
                         budget = :budget, estimated_completion = :estimated_completion, github_repo = :github_repo, 
                         deployment_url = :deployment_url, admin_notes = :admin_notes WHERE id = :project_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':project_name', $project_name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':requirements', $requirements);
                $stmt->bindParam(':budget', $budget);
                $stmt->bindParam(':estimated_completion', $estimated_completion);
                $stmt->bindParam(':github_repo', $github_repo);
                $stmt->bindParam(':deployment_url', $deployment_url);
                $stmt->bindParam(':admin_notes', $admin_notes);
                $stmt->bindParam(':project_id', $project_id);
                
                if ($stmt->execute()) {
                    $message = "Project updated successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to update project";
                    $message_type = "error";
                }
                break;
                
            case 'update_status':
                $project_id = $_POST['project_id'];
                $new_status = $_POST['status'];
                $admin_notes = trim($_POST['admin_notes']);
                
                // Get current status
                $current_query = "SELECT status FROM projects WHERE id = :project_id";
                $current_stmt = $conn->prepare($current_query);
                $current_stmt->bindParam(':project_id', $project_id);
                $current_stmt->execute();
                $current_project = $current_stmt->fetch(PDO::FETCH_ASSOC);
                $old_status = $current_project['status'];
                
                // Update project status
                $query = "UPDATE projects SET status = :status, admin_notes = :admin_notes";
                if ($new_status == 'in_progress' && $old_status == 'approved') {
                    $query .= ", start_date = CURDATE()";
                } elseif ($new_status == 'completed') {
                    $query .= ", end_date = CURDATE()";
                }
                $query .= " WHERE id = :project_id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':status', $new_status);
                $stmt->bindParam(':admin_notes', $admin_notes);
                $stmt->bindParam(':project_id', $project_id);
                
                if ($stmt->execute()) {
                    // Log status change
                    $history_query = "INSERT INTO project_status_history (project_id, old_status, new_status, admin_id, notes) 
                                     VALUES (:project_id, :old_status, :new_status, :admin_id, :notes)";
                    $history_stmt = $conn->prepare($history_query);
                    $history_stmt->bindParam(':project_id', $project_id);
                    $history_stmt->bindParam(':old_status', $old_status);
                    $history_stmt->bindParam(':new_status', $new_status);
                    $history_stmt->bindParam(':admin_id', $_SESSION['user_id']);
                    $history_stmt->bindParam(':notes', $admin_notes);
                    $history_stmt->execute();
                    
                    $message = "Project status updated successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to update project status";
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                $project_id = $_POST['project_id'];
                
                $query = "DELETE FROM projects WHERE id = :project_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':project_id', $project_id);
                
                if ($stmt->execute()) {
                    $message = "Project deleted successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete project";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get all projects with client information
$query = "SELECT p.*, u.first_name, u.last_name, u.company, u.email 
          FROM projects p 
          JOIN users u ON p.client_id = u.id 
          ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all clients for dropdown
$clients_query = "SELECT id, first_name, last_name, company FROM users WHERE role = 'client' AND status = 'active' ORDER BY first_name";
$clients_stmt = $conn->prepare($clients_query);
$clients_stmt->execute();
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management - Zero One Labs</title>
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
            --vscode-card-bg: #ffffff;
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

        .vscode-button {
            background: var(--vscode-accent);
            color: white;
        }

        .vscode-button:hover {
            background: var(--vscode-accent-hover);
        }

        .vscode-input {
            background: var(--vscode-card-bg);
            border: 1px solid var(--vscode-border);
            color: var(--vscode-text);
        }
    </style>
</head>
<body>
    <div class="min-h-screen p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">Project Management</h1>
                <p class="text-sm" style="color: var(--vscode-text-muted);">Manage client projects and track progress</p>
            </div>
            <div class="flex space-x-3">
                <a href="../dashboard-admin.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <button onclick="openCreateModal()" class="vscode-button px-4 py-2 rounded">
                    <i class="fas fa-plus mr-2"></i>Add Project
                </button>
            </div>
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

        <!-- Projects Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($projects as $project): ?>
            <div class="vscode-card rounded-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold mb-1"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                        <p class="text-sm" style="color: var(--vscode-text-muted);">
                            <?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?>
                            <?php if ($project['company']): ?>
                                • <?php echo htmlspecialchars($project['company']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php
                    $status_colors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-blue-100 text-blue-800',
                        'in_progress' => 'bg-purple-100 text-purple-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800'
                    ];
                    ?>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$project['status']]; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                    </span>
                </div>
                
                <p class="text-sm mb-4" style="color: var(--vscode-text-muted);">
                    <?php echo htmlspecialchars(substr($project['description'], 0, 100)) . (strlen($project['description']) > 100 ? '...' : ''); ?>
                </p>
                
                <div class="space-y-2 text-xs mb-4" style="color: var(--vscode-text-muted);">
                    <?php if ($project['budget']): ?>
                    <div><i class="fas fa-dollar-sign mr-2"></i>Budget: $<?php echo number_format($project['budget'], 0); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($project['estimated_completion']) && $project['estimated_completion']): ?>
                    <div><i class="fas fa-calendar mr-2"></i>Due: <?php echo date('M j, Y', strtotime($project['estimated_completion'])); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($project['github_repo']) && $project['github_repo']): ?>
                    <div><i class="fab fa-github mr-2"></i><a href="<?php echo htmlspecialchars($project['github_repo']); ?>" target="_blank" class="hover:underline">Repository</a></div>
                    <?php endif; ?>
                    
                    <?php if (isset($project['deployment_url']) && $project['deployment_url']): ?>
                    <div><i class="fas fa-external-link-alt mr-2"></i><a href="<?php echo htmlspecialchars($project['deployment_url']); ?>" target="_blank" class="hover:underline">Live Site</a></div>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-between items-center">
                    <div class="flex space-x-2">
                        <button onclick="viewProject(<?php echo $project['id']; ?>)" class="text-blue-600 hover:text-blue-800 p-1" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editProject(<?php echo $project['id']; ?>)" class="text-green-600 hover:text-green-800 p-1" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="updateStatus(<?php echo $project['id']; ?>)" class="text-purple-600 hover:text-purple-800 p-1" title="Update Status">
                            <i class="fas fa-tasks"></i>
                        </button>
                        <button onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['project_name']); ?>')" class="text-red-600 hover:text-red-800 p-1" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="text-xs" style="color: var(--vscode-text-muted);">
                        <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($projects)): ?>
        <div class="vscode-card rounded-lg p-12 text-center">
            <i class="fas fa-project-diagram text-4xl mb-4" style="color: var(--vscode-text-muted);"></i>
            <h3 class="text-lg font-medium mb-2">No projects found</h3>
            <p class="mb-4" style="color: var(--vscode-text-muted);">Start by creating your first project</p>
            <button onclick="openCreateModal()" class="vscode-button px-6 py-2 rounded">
                <i class="fas fa-plus mr-2"></i>Create First Project
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Create/Edit Project Modal -->
    <div id="projectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-xl font-semibold">Add New Project</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="projectForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="project_id" id="projectId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Client</label>
                        <select name="client_id" id="client_id" required class="vscode-input w-full px-3 py-2 rounded">
                            <option value="">Select Client</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>">
                                <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                <?php if ($client['company']): ?>
                                    (<?php echo htmlspecialchars($client['company']); ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Project Name</label>
                        <input type="text" name="project_name" id="project_name" required class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2">Description</label>
                        <textarea name="description" id="description" rows="3" required class="vscode-input w-full px-3 py-2 rounded"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2">Requirements</label>
                        <textarea name="requirements" id="requirements" rows="3" class="vscode-input w-full px-3 py-2 rounded"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Budget ($)</label>
                        <input type="number" name="budget" id="budget" step="0.01" class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Estimated Completion</label>
                        <input type="date" name="estimated_completion" id="estimated_completion" class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">GitHub Repository</label>
                        <input type="url" name="github_repo" id="github_repo" class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Deployment URL</label>
                        <input type="url" name="deployment_url" id="deployment_url" class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div class="md:col-span-2" id="adminNotesField" style="display: none;">
                        <label class="block text-sm font-medium mb-2">Admin Notes</label>
                        <textarea name="admin_notes" id="admin_notes" rows="3" class="vscode-input w-full px-3 py-2 rounded"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="vscode-button px-4 py-2 rounded">
                        <i class="fas fa-save mr-2"></i><span id="submitText">Create Project</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Update Project Status</h3>
                <button onclick="closeStatusModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="project_id" id="statusProjectId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">New Status</label>
                        <select name="status" id="newStatus" required class="vscode-input w-full px-3 py-2 rounded">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Notes</label>
                        <textarea name="admin_notes" rows="3" class="vscode-input w-full px-3 py-2 rounded" placeholder="Add notes about this status change..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="vscode-button px-4 py-2 rounded">
                        <i class="fas fa-save mr-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Project Modal -->
    <div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-6xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Project Details</h3>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="projectDetails" class="space-y-6">
                <!-- Loading state -->
                <div class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                    <p class="mt-2 text-gray-600">Loading project details...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Project';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitText').textContent = 'Create Project';
    document.getElementById('adminNotesField').style.display = 'none';
    document.getElementById('client_id').disabled = false;
    document.getElementById('projectForm').reset();
    document.getElementById('projectModal').classList.remove('hidden');
    document.getElementById('projectModal').classList.add('flex');
}

function editProject(projectId) {
    fetch(`../api/projects.php?id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Project';
            document.getElementById('formAction').value = 'update';
            document.getElementById('submitText').textContent = 'Update Project';
            document.getElementById('adminNotesField').style.display = 'block';
            document.getElementById('client_id').disabled = true;
            
            document.getElementById('projectId').value = data.id;
            document.getElementById('client_id').value = data.client_id;
            document.getElementById('project_name').value = data.project_name;
            document.getElementById('description').value = data.description;
            document.getElementById('requirements').value = data.requirements || '';
            document.getElementById('budget').value = data.budget || '';
            document.getElementById('estimated_completion').value = data.estimated_completion || '';
            document.getElementById('github_repo').value = data.github_repo || '';
            document.getElementById('deployment_url').value = data.deployment_url || '';
            document.getElementById('admin_notes').value = data.admin_notes || '';
            
            document.getElementById('projectModal').classList.remove('hidden');
            document.getElementById('projectModal').classList.add('flex');
        })
        .catch(error => {
            alert('Error loading project data');
        });
}

function updateStatus(projectId) {
    document.getElementById('statusProjectId').value = projectId;
    document.getElementById('statusModal').classList.remove('hidden');
    document.getElementById('statusModal').classList.add('flex');
}

function viewProject(projectId) {
    // Show modal and load project details
    document.getElementById('viewModal').classList.remove('hidden');
    document.getElementById('viewModal').classList.add('flex');
    
    // Load project details with history
    fetch(`../api/projects.php?id=${projectId}&history=true`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            displayProjectDetails(data);
        })
        .catch(error => {
            document.getElementById('projectDetails').innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                    <p class="mt-2 text-red-600">Error loading project details: ${error.message}</p>
                </div>
            `;
        });
}

function displayProjectDetails(project) {
    const statusColors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-blue-100 text-blue-800',
        'in_progress': 'bg-purple-100 text-purple-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
    };
    
    const formatDate = (dateString) => {
        if (!dateString) return 'Not set';
        return new Date(dateString).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    };
    
    const formatDateTime = (dateString) => {
        if (!dateString) return '';
        return new Date(dateString).toLocaleString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    
    document.getElementById('projectDetails').innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Project Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Project Header -->
                <div class="vscode-card rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">${project.project_name}</h2>
                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                <span>Project ID: #${project.id}</span>
                                <span>Created: ${formatDate(project.created_at)}</span>
                            </div>
                        </div>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full ${statusColors[project.status]}">
                            ${project.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                        </span>
                    </div>
                    
                    <div class="prose max-w-none">
                        <h3 class="text-lg font-semibold mb-2">Description</h3>
                        <p class="text-gray-700 mb-4">${project.description ? project.description.replace(/\n/g, '<br>') : 'No description provided'}</p>
                        
                        ${project.requirements ? `
                        <h3 class="text-lg font-semibold mb-2">Requirements</h3>
                        <p class="text-gray-700 mb-4">${project.requirements.replace(/\n/g, '<br>')}</p>
                        ` : ''}
                    </div>
                </div>

                <!-- Project Details -->
                <div class="vscode-card rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Project Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Budget</label>
                            <p class="text-lg">
                                ${project.budget ? `$${parseFloat(project.budget).toLocaleString()}` : '<span class="text-gray-500">Not specified</span>'}
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">GitHub Repository</label>
                            <p class="text-lg">
                                ${project.github_repo ? 
                                    `<a href="${project.github_repo}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                        <i class="fab fa-github mr-1"></i>View Repository
                                    </a>` : 
                                    '<span class="text-gray-500">Not provided</span>'
                                }
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Deployment URL</label>
                            <p class="text-lg">
                                ${project.deployment_url ? 
                                    `<a href="${project.deployment_url}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-external-link-alt mr-1"></i>Live Site
                                    </a>` : 
                                    '<span class="text-gray-500">Not deployed</span>'
                                }
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Estimated Completion</label>
                            <p class="text-lg">
                                ${project.estimated_completion ? formatDate(project.estimated_completion) : '<span class="text-gray-500">Not set</span>'}
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Start Date</label>
                            <p class="text-lg">
                                ${project.start_date ? formatDate(project.start_date) : '<span class="text-gray-500">Not started</span>'}
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">End Date</label>
                            <p class="text-lg">
                                ${project.end_date ? formatDate(project.end_date) : '<span class="text-gray-500">Not completed</span>'}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status History -->
                <div class="vscode-card rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Status History</h3>
                    ${project.status_history && project.status_history.length > 0 ? `
                        <div class="space-y-4">
                            ${project.status_history.map(history => `
                                <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full mt-2"></div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusColors[history.new_status]}">
                                                    ${history.new_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                </span>
                                                ${history.old_status ? `
                                                    <span class="text-gray-500 text-sm ml-2">
                                                        from ${history.old_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                                    </span>
                                                ` : ''}
                                            </div>
                                            <span class="text-sm text-gray-500">
                                                ${formatDateTime(history.changed_at)}
                                            </span>
                                        </div>
                                        ${history.notes ? `
                                            <p class="text-gray-700 mt-2">${history.notes.replace(/\n/g, '<br>')}</p>
                                        ` : ''}
                                        ${history.first_name ? `
                                            <p class="text-sm text-gray-500 mt-1">
                                                Updated by: ${history.first_name} ${history.last_name}
                                            </p>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p class="text-gray-500">No status changes recorded yet.</p>'}
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Client Information -->
                <div class="vscode-card rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Client Information</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Name</label>
                            <p class="font-medium">${project.first_name} ${project.last_name}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Company</label>
                            <p>${project.company || 'N/A'}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                            <p><a href="mailto:${project.email}" class="text-blue-600 hover:text-blue-800">
                                ${project.email}
                            </a></p>
                        </div>
                        
                        ${project.phone ? `
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Phone</label>
                            <p><a href="tel:${project.phone}" class="text-blue-600 hover:text-blue-800">
                                ${project.phone}
                            </a></p>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Admin Notes -->
                ${project.admin_notes ? `
                <div class="vscode-card rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Admin Notes</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-gray-700">${project.admin_notes.replace(/\n/g, '<br>')}</p>
                    </div>
                </div>
                ` : ''}

                <!-- Quick Actions -->
                <div class="vscode-card rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <button onclick="editProject(${project.id})" class="w-full vscode-button px-4 py-2 rounded text-left">
                            <i class="fas fa-edit mr-2"></i>Edit Project
                        </button>
                        <button onclick="updateStatus(${project.id})" class="w-full vscode-button px-4 py-2 rounded text-left">
                            <i class="fas fa-tasks mr-2"></i>Update Status
                        </button>
                        <button onclick="deleteProject(${project.id}, '${project.project_name.replace(/'/g, "\\'")}')" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-left">
                            <i class="fas fa-trash mr-2"></i>Delete Project
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function deleteProject(projectId, projectName) {
    if (confirm(`Are you sure you want to delete "${projectName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="project_id" value="${projectId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function closeModal() {
    document.getElementById('projectModal').classList.add('hidden');
    document.getElementById('projectModal').classList.remove('flex');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
    document.getElementById('statusModal').classList.remove('flex');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
    document.getElementById('viewModal').classList.remove('flex');
}

// Close modals when clicking outside
document.getElementById('projectModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) closeStatusModal();
});

document.getElementById('viewModal').addEventListener('click', function(e) {
    if (e.target === this) closeViewModal();
});
</script>
</body>
</html>

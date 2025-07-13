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
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $technologies = trim($_POST['technologies']);
                $category = trim($_POST['category']);
                $demo_url = trim($_POST['demo_url']);
                $github_url = trim($_POST['github_url']);
                $featured = isset($_POST['featured']) ? 1 : 0;
                $display_order = $_POST['display_order'];
                
                // Handle image upload
                $image_url = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $upload_dir = '../uploads/portfolio/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_url = 'uploads/portfolio/' . $file_name;
                    }
                }
                
                $query = "INSERT INTO portfolio_projects (title, description, technologies, category, image_url, demo_url, github_url, featured, display_order) VALUES (:title, :description, :technologies, :category, :image_url, :demo_url, :github_url, :featured, :display_order)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':technologies', $technologies);
                $stmt->bindParam(':category', $category);
                $stmt->bindParam(':image_url', $image_url);
                $stmt->bindParam(':demo_url', $demo_url);
                $stmt->bindParam(':github_url', $github_url);
                $stmt->bindParam(':featured', $featured);
                $stmt->bindParam(':display_order', $display_order);
                
                if ($stmt->execute()) {
                    $message = "Portfolio project added successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to add portfolio project";
                    $message_type = "error";
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $technologies = trim($_POST['technologies']);
                $category = trim($_POST['category']);
                $demo_url = trim($_POST['demo_url']);
                $github_url = trim($_POST['github_url']);
                $featured = isset($_POST['featured']) ? 1 : 0;
                $display_order = $_POST['display_order'];
                $status = $_POST['status'];
                
                // Handle image upload
                $image_url = $_POST['current_image'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $upload_dir = '../uploads/portfolio/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        // Delete old image if exists
                        if ($_POST['current_image'] && file_exists('../' . $_POST['current_image'])) {
                            unlink('../' . $_POST['current_image']);
                        }
                        $image_url = 'uploads/portfolio/' . $file_name;
                    }
                }
                
                $query = "UPDATE portfolio_projects SET title = :title, description = :description, technologies = :technologies, category = :category, image_url = :image_url, demo_url = :demo_url, github_url = :github_url, featured = :featured, display_order = :display_order, status = :status WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':technologies', $technologies);
                $stmt->bindParam(':category', $category);
                $stmt->bindParam(':image_url', $image_url);
                $stmt->bindParam(':demo_url', $demo_url);
                $stmt->bindParam(':github_url', $github_url);
                $stmt->bindParam(':featured', $featured);
                $stmt->bindParam(':display_order', $display_order);
                $stmt->bindParam(':status', $status);
                
                if ($stmt->execute()) {
                    $message = "Portfolio project updated successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to update portfolio project";
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Get image URL before deleting
                $query = "SELECT image_url FROM portfolio_projects WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $project = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Delete image file if exists
                if ($project['image_url'] && file_exists('../' . $project['image_url'])) {
                    unlink('../' . $project['image_url']);
                }
                
                $query = "DELETE FROM portfolio_projects WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    $message = "Portfolio project deleted successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete portfolio project";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get all portfolio projects
$query = "SELECT * FROM portfolio_projects ORDER BY display_order, title";
$stmt = $conn->prepare($query);
$stmt->execute();
$portfolio_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Management - Zero One Labs</title>
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
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900">Portfolio Management</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="../dashboard-admin.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                        <button onclick="openCreateModal()" class="vscode-button px-4 py-2 rounded-lg">
                            <i class="fas fa-plus mr-2"></i>Add New Project
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Portfolio Projects Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($portfolio_projects as $project): ?>
                <div class="vscode-card rounded-lg overflow-hidden">
                    <?php if ($project['image_url']): ?>
                    <div class="h-48 bg-gray-200">
                        <img src="../<?php echo htmlspecialchars($project['image_url']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="w-full h-full object-cover">
                    </div>
                    <?php else: ?>
                    <div class="h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <i class="fas fa-image text-white text-4xl"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($project['title']); ?></h3>
                            <div class="flex items-center space-x-2">
                                <?php if ($project['featured']): ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Featured</span>
                                <?php endif; ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $project['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <p class="text-sm mb-4 text-gray-600">
                            <?php echo htmlspecialchars(substr($project['description'], 0, 100)) . (strlen($project['description']) > 100 ? '...' : ''); ?>
                        </p>
                        
                        <?php if ($project['technologies']): ?>
                        <div class="mb-4">
                            <div class="flex flex-wrap gap-1">
                                <?php 
                                $techs = explode(',', $project['technologies']);
                                foreach (array_slice($techs, 0, 3) as $tech): ?>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded"><?php echo htmlspecialchars(trim($tech)); ?></span>
                                <?php endforeach;
                                if (count($techs) > 3): ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">+<?php echo count($techs) - 3; ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between items-center text-sm text-gray-500 mb-4">
                            <span>Category: <?php echo htmlspecialchars($project['category']); ?></span>
                            <span>Order: <?php echo $project['display_order']; ?></span>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($project)); ?>)" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <button onclick="deleteProject(<?php echo $project['id']; ?>)" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Add New Portfolio Project</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Project Title</label>
                        <input type="text" name="title" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="vscode-input w-full rounded-lg px-3 py-2" required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Technologies (comma-separated)</label>
                        <input type="text" name="technologies" placeholder="React, Node.js, MongoDB" class="vscode-input w-full rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="vscode-input w-full rounded-lg px-3 py-2" required>
                            <option value="">Select Category</option>
                            <option value="web-app">Web Application</option>
                            <option value="website">Website</option>
                            <option value="api">API</option>
                            <option value="mobile">Mobile App</option>
                            <option value="ecommerce">E-commerce</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Project Image</label>
                        <input type="file" name="image" accept="image/*" class="vscode-input w-full rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Demo URL</label>
                        <input type="url" name="demo_url" placeholder="https://demo.example.com" class="vscode-input w-full rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">GitHub URL</label>
                        <input type="url" name="github_url" placeholder="https://github.com/user/repo" class="vscode-input w-full rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="featured" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Featured Project</span>
                        </label>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                        <input type="number" name="display_order" value="0" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            Cancel
                        </button>
                        <button type="submit" class="vscode-button px-4 py-2 rounded-lg">
                            Add Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Edit Portfolio Project</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="current_image" id="edit_current_image">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Project Title</label>
                        <input type="text" name="title" id="edit_title" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="edit_description" rows="3" class="vscode-input w-full rounded-lg px-3 py-2" required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Technologies (comma-separated)</label>
                        <input type="text" name="technologies" id="edit_technologies" class="vscode-input w-full rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" id="edit_category" class="vscode-input w-full rounded-lg px-3 py-2" required>
                            <option value="web-app">Web Application</option>
                            <option value="website">Website</option>
                            <option value="api">API</option>
                            <option value="mobile">Mobile App</option>
                            <option value="ecommerce">E-commerce</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Project Image</label>
                        <input type="file" name="image" accept="image/*" class="vscode-input w-full rounded-lg px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to keep current image</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Demo URL</label>
                        <input type="url" name="demo_url" id="edit_demo_url" class="vscode-input w-full rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">GitHub URL</label>
                        <input type="url" name="github_url" id="edit_github_url" class="vscode-input w-full rounded-lg px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="featured" id="edit_featured" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Featured Project</span>
                        </label>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                        <input type="number" name="display_order" id="edit_display_order" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" id="edit_status" class="vscode-input w-full rounded-lg px-3 py-2" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            Cancel
                        </button>
                        <button type="submit" class="vscode-button px-4 py-2 rounded-lg">
                            Update Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Confirm Delete</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete this portfolio project? This action cannot be undone.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            Cancel
                        </button>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function openEditModal(project) {
            document.getElementById('edit_id').value = project.id;
            document.getElementById('edit_title').value = project.title;
            document.getElementById('edit_description').value = project.description;
            document.getElementById('edit_technologies').value = project.technologies;
            document.getElementById('edit_category').value = project.category;
            document.getElementById('edit_demo_url').value = project.demo_url;
            document.getElementById('edit_github_url').value = project.github_url;
            document.getElementById('edit_featured').checked = project.featured == 1;
            document.getElementById('edit_display_order').value = project.display_order;
            document.getElementById('edit_status').value = project.status;
            document.getElementById('edit_current_image').value = project.image_url;
            
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function deleteProject(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('fixed')) {
                e.target.classList.add('hidden');
            }
        });
    </script>
</body>
</html> 
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
                $section = trim($_POST['section']);
                $title = trim($_POST['title']);
                $content = trim($_POST['content']);
                $display_order = $_POST['display_order'];
                
                $query = "INSERT INTO company_info (section, title, content, display_order) VALUES (:section, :title, :content, :display_order)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':section', $section);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':content', $content);
                $stmt->bindParam(':display_order', $display_order);
                
                if ($stmt->execute()) {
                    $message = "Company information added successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to add company information";
                    $message_type = "error";
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $section = trim($_POST['section']);
                $title = trim($_POST['title']);
                $content = trim($_POST['content']);
                $display_order = $_POST['display_order'];
                $status = $_POST['status'];
                
                $query = "UPDATE company_info SET section = :section, title = :title, content = :content, display_order = :display_order, status = :status WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':section', $section);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':content', $content);
                $stmt->bindParam(':display_order', $display_order);
                $stmt->bindParam(':status', $status);
                
                if ($stmt->execute()) {
                    $message = "Company information updated successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to update company information";
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                $query = "DELETE FROM company_info WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    $message = "Company information deleted successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete company information";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get all company information
$query = "SELECT * FROM company_info ORDER BY display_order, section";
$stmt = $conn->prepare($query);
$stmt->execute();
$company_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique sections for dropdown
$sections_query = "SELECT DISTINCT section FROM company_info ORDER BY section";
$sections_stmt = $conn->prepare($sections_query);
$sections_stmt->execute();
$sections = $sections_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Information Management - Zero One Labs</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Company Information Management</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="../dashboard-admin.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                        <button onclick="openCreateModal()" class="vscode-button px-4 py-2 rounded-lg">
                            <i class="fas fa-plus mr-2"></i>Add New
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

            <!-- Company Information Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($company_info as $info): ?>
                <div class="vscode-card rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold mb-1"><?php echo htmlspecialchars($info['title']); ?></h3>
                            <p class="text-sm text-gray-600">Section: <?php echo htmlspecialchars($info['section']); ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $info['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst($info['status']); ?>
                        </span>
                    </div>
                    
                    <p class="text-sm mb-4 text-gray-600">
                        <?php echo htmlspecialchars(substr($info['content'], 0, 100)) . (strlen($info['content']) > 100 ? '...' : ''); ?>
                    </p>
                    
                    <div class="flex justify-between items-center text-sm text-gray-500 mb-4">
                        <span>Order: <?php echo $info['display_order']; ?></span>
                        <span><?php echo date('M j, Y', strtotime($info['updated_at'])); ?></span>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($info)); ?>)" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button onclick="deleteInfo(<?php echo $info['id']; ?>)" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
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
                <h3 class="text-lg font-semibold mb-4">Add Company Information</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                        <select name="section" class="vscode-input w-full rounded-lg px-3 py-2" required>
                            <option value="">Select Section</option>
                            <option value="about">About</option>
                            <option value="mission">Mission</option>
                            <option value="vision">Vision</option>
                            <option value="values">Values</option>
                            <option value="team">Team</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text" name="title" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <textarea name="content" rows="4" class="vscode-input w-full rounded-lg px-3 py-2" required></textarea>
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
                            Add Information
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
                <h3 class="text-lg font-semibold mb-4">Edit Company Information</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                        <select name="section" id="edit_section" class="vscode-input w-full rounded-lg px-3 py-2" required>
                            <option value="about">About</option>
                            <option value="mission">Mission</option>
                            <option value="vision">Vision</option>
                            <option value="values">Values</option>
                            <option value="team">Team</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text" name="title" id="edit_title" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <textarea name="content" id="edit_content" rows="4" class="vscode-input w-full rounded-lg px-3 py-2" required></textarea>
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
                            Update Information
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
                <p class="text-gray-600 mb-6">Are you sure you want to delete this company information? This action cannot be undone.</p>
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

        function openEditModal(info) {
            document.getElementById('edit_id').value = info.id;
            document.getElementById('edit_section').value = info.section;
            document.getElementById('edit_title').value = info.title;
            document.getElementById('edit_content').value = info.content;
            document.getElementById('edit_display_order').value = info.display_order;
            document.getElementById('edit_status').value = info.status;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function deleteInfo(id) {
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
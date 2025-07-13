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
                $service_name = trim($_POST['service_name']);
                $description = trim($_POST['description']);
                $icon = trim($_POST['icon']);
                $price_range = trim($_POST['price_range']);
                $features = $_POST['features'];
                $display_order = $_POST['display_order'];
                
                // Convert features array to JSON
                $features_array = array_filter(explode("\n", $features));
                $features_json = json_encode($features_array);
                
                $query = "INSERT INTO services (service_name, description, icon, price_range, features, display_order) VALUES (:service_name, :description, :icon, :price_range, :features, :display_order)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':service_name', $service_name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':icon', $icon);
                $stmt->bindParam(':price_range', $price_range);
                $stmt->bindParam(':features', $features_json);
                $stmt->bindParam(':display_order', $display_order);
                
                if ($stmt->execute()) {
                    $message = "Service added successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to add service";
                    $message_type = "error";
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $service_name = trim($_POST['service_name']);
                $description = trim($_POST['description']);
                $icon = trim($_POST['icon']);
                $price_range = trim($_POST['price_range']);
                $features = $_POST['features'];
                $display_order = $_POST['display_order'];
                $status = $_POST['status'];
                
                // Convert features array to JSON
                $features_array = array_filter(explode("\n", $features));
                $features_json = json_encode($features_array);
                
                $query = "UPDATE services SET service_name = :service_name, description = :description, icon = :icon, price_range = :price_range, features = :features, display_order = :display_order, status = :status WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':service_name', $service_name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':icon', $icon);
                $stmt->bindParam(':price_range', $price_range);
                $stmt->bindParam(':features', $features_json);
                $stmt->bindParam(':display_order', $display_order);
                $stmt->bindParam(':status', $status);
                
                if ($stmt->execute()) {
                    $message = "Service updated successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to update service";
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                $query = "DELETE FROM services WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if ($stmt->execute()) {
                    $message = "Service deleted successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete service";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get all services
$query = "SELECT * FROM services ORDER BY display_order, service_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - Zero One Labs</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Services Management</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="../dashboard-admin.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                        <button onclick="openCreateModal()" class="vscode-button px-4 py-2 rounded-lg">
                            <i class="fas fa-plus mr-2"></i>Add New Service
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

            <!-- Services Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($services as $service): ?>
                <div class="vscode-card rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center">
                            <i class="<?php echo htmlspecialchars($service['icon']); ?> text-2xl text-blue-600 mr-3"></i>
                            <div>
                                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($service['price_range']); ?></p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $service['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst($service['status']); ?>
                        </span>
                    </div>
                    
                    <p class="text-sm mb-4 text-gray-600">
                        <?php echo htmlspecialchars(substr($service['description'], 0, 100)) . (strlen($service['description']) > 100 ? '...' : ''); ?>
                    </p>
                    
                    <?php if ($service['features']): ?>
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Features:</h4>
                        <div class="flex flex-wrap gap-1">
                            <?php 
                            $features = json_decode($service['features'], true);
                            if ($features) {
                                foreach (array_slice($features, 0, 3) as $feature): ?>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded"><?php echo htmlspecialchars($feature); ?></span>
                                <?php endforeach;
                                if (count($features) > 3): ?>
                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">+<?php echo count($features) - 3; ?> more</span>
                                <?php endif;
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-between items-center text-sm text-gray-500 mb-4">
                        <span>Order: <?php echo $service['display_order']; ?></span>
                        <span><?php echo date('M j, Y', strtotime($service['updated_at'])); ?></span>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($service)); ?>)" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button onclick="deleteService(<?php echo $service['id']; ?>)" class="text-red-600 hover:text-red-800">
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
                <h3 class="text-lg font-semibold mb-4">Add New Service</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Name</label>
                        <input type="text" name="service_name" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="vscode-input w-full rounded-lg px-3 py-2" required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Icon (FontAwesome class)</label>
                        <input type="text" name="icon" placeholder="fas fa-code" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                        <input type="text" name="price_range" placeholder="$2,000 - $10,000" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Features (one per line)</label>
                        <textarea name="features" rows="4" placeholder="Responsive Design&#10;SEO Optimization&#10;Performance Optimization" class="vscode-input w-full rounded-lg px-3 py-2"></textarea>
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
                            Add Service
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
                <h3 class="text-lg font-semibold mb-4">Edit Service</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Service Name</label>
                        <input type="text" name="service_name" id="edit_service_name" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="edit_description" rows="3" class="vscode-input w-full rounded-lg px-3 py-2" required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Icon (FontAwesome class)</label>
                        <input type="text" name="icon" id="edit_icon" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                        <input type="text" name="price_range" id="edit_price_range" class="vscode-input w-full rounded-lg px-3 py-2" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Features (one per line)</label>
                        <textarea name="features" id="edit_features" rows="4" class="vscode-input w-full rounded-lg px-3 py-2"></textarea>
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
                            Update Service
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
                <p class="text-gray-600 mb-6">Are you sure you want to delete this service? This action cannot be undone.</p>
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

        function openEditModal(service) {
            document.getElementById('edit_id').value = service.id;
            document.getElementById('edit_service_name').value = service.service_name;
            document.getElementById('edit_description').value = service.description;
            document.getElementById('edit_icon').value = service.icon;
            document.getElementById('edit_price_range').value = service.price_range;
            document.getElementById('edit_display_order').value = service.display_order;
            document.getElementById('edit_status').value = service.status;
            
            // Convert features JSON to text
            let features = '';
            if (service.features) {
                try {
                    const featuresArray = JSON.parse(service.features);
                    features = featuresArray.join('\n');
                } catch (e) {
                    features = service.features;
                }
            }
            document.getElementById('edit_features').value = features;
            
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function deleteService(id) {
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
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
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $company = trim($_POST['company']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                
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
                    
                    $query = "INSERT INTO users (username, email, password, role, first_name, last_name, company, phone, address, status) 
                             VALUES (:username, :email, :password, 'client', :first_name, :last_name, :company, :phone, :address, 'active')";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':first_name', $first_name);
                    $stmt->bindParam(':last_name', $last_name);
                    $stmt->bindParam(':company', $company);
                    $stmt->bindParam(':phone', $phone);
                    $stmt->bindParam(':address', $address);
                    
                    if ($stmt->execute()) {
                        $message = "Client created successfully";
                        $message_type = "success";
                    } else {
                        $message = "Failed to create client";
                        $message_type = "error";
                    }
                }
                break;
                
            case 'update':
                $user_id = $_POST['user_id'];
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $company = trim($_POST['company']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $status = $_POST['status'];
                
                $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, 
                         company = :company, phone = :phone, address = :address, status = :status 
                         WHERE id = :user_id AND role = 'client'";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':company', $company);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $message = "Client updated successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to update client";
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                $user_id = $_POST['user_id'];
                
                $query = "DELETE FROM users WHERE id = :user_id AND role = 'client'";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $message = "Client deleted successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete client";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get all clients
$query = "SELECT * FROM users WHERE role = 'client' ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get client for editing
$edit_client = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $query = "SELECT * FROM users WHERE id = :id AND role = 'client'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $edit_id);
    $stmt->execute();
    $edit_client = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - Zero One Labs</title>
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

        [data-theme="dark"] {
            --vscode-bg: #1e1e1e;
            --vscode-sidebar: #252526;
            --vscode-editor: #1e1e1e;
            --vscode-text: #cccccc;
            --vscode-text-muted: #969696;
            --vscode-border: #3e3e42;
            --vscode-accent: #007acc;
            --vscode-accent-hover: #1177bb;
            --vscode-card-bg: #252526;
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
<body data-theme="light">
    <div class="min-h-screen p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">Client Management</h1>
                <p class="text-sm" style="color: var(--vscode-text-muted);">Manage client records and information</p>
            </div>
            <div class="flex space-x-3">
                <a href="../dashboard-admin.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <button onclick="openCreateModal()" class="vscode-button px-4 py-2 rounded">
                    <i class="fas fa-plus mr-2"></i>Add Client
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

        <!-- Clients Table -->
        <div class="vscode-card rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b" style="border-color: var(--vscode-border);">
                <h3 class="text-lg font-semibold">All Clients (<?php echo count($clients); ?>)</h3>
            </div>
            
            <?php if (empty($clients)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-users text-4xl mb-4" style="color: var(--vscode-text-muted);"></i>
                <h3 class="text-lg font-medium mb-2">No clients found</h3>
                <p class="mb-4" style="color: var(--vscode-text-muted);">Start by adding your first client</p>
                <button onclick="openCreateModal()" class="vscode-button px-6 py-2 rounded">
                    <i class="fas fa-plus mr-2"></i>Add First Client
                </button>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead style="background: var(--vscode-sidebar);">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--vscode-text-muted);">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="divide-color: var(--vscode-border);">
                        <?php foreach ($clients as $client): ?>
                        <tr class="hover:bg-opacity-50" style="hover:background-color: var(--vscode-sidebar);">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                        <?php echo strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1)); ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></div>
                                        <div class="text-sm" style="color: var(--vscode-text-muted);">@<?php echo htmlspecialchars($client['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm"><?php echo htmlspecialchars($client['email']); ?></div>
                                <div class="text-sm" style="color: var(--vscode-text-muted);"><?php echo htmlspecialchars($client['phone'] ?: 'No phone'); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm"><?php echo htmlspecialchars($client['company'] ?: 'No company'); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $status_colors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'inactive' => 'bg-red-100 text-red-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800'
                                ];
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$client['status']]; ?>">
                                    <?php echo ucfirst($client['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm" style="color: var(--vscode-text-muted);">
                                <?php echo date('M j, Y', strtotime($client['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex space-x-2">
                                    <button onclick="viewClient(<?php echo $client['id']; ?>)" class="text-blue-600 hover:text-blue-800 p-1" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editClient(<?php echo $client['id']; ?>)" class="text-green-600 hover:text-green-800 p-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteClient(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>')" class="text-red-600 hover:text-red-800 p-1" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="clientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-xl font-semibold">Add New Client</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="clientForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="user_id" id="userId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Username</label>
                        <input type="text" name="username" id="username" required class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Email</label>
                        <input type="email" name="email" id="email" required class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">First Name</label>
                        <input type="text" name="first_name" id="first_name" required class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Last Name</label>
                        <input type="text" name="last_name" id="last_name" required class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Company</label>
                        <input type="text" name="company" id="company" class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Phone</label>
                        <input type="tel" name="phone" id="phone" class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2">Address</label>
                        <textarea name="address" id="address" rows="3" class="vscode-input w-full px-3 py-2 rounded"></textarea>
                    </div>
                    <div id="passwordField">
                        <label class="block text-sm font-medium mb-2">Password</label>
                        <input type="password" name="password" id="password" class="vscode-input w-full px-3 py-2 rounded">
                    </div>
                    <div id="statusField" class="hidden">
                        <label class="block text-sm font-medium mb-2">Status</label>
                        <select name="status" id="status" class="vscode-input w-full px-3 py-2 rounded">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="vscode-button px-4 py-2 rounded">
                        <i class="fas fa-save mr-2"></i><span id="submitText">Create Client</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Client Modal -->
    <div id="viewClientModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Client Details</h3>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div id="clientDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add New Client';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitText').textContent = 'Create Client';
    document.getElementById('passwordField').classList.remove('hidden');
    document.getElementById('statusField').classList.add('hidden');
    document.getElementById('password').required = true;
    document.getElementById('username').readOnly = false;
    document.getElementById('clientForm').reset();
    document.getElementById('clientModal').classList.remove('hidden');
    document.getElementById('clientModal').classList.add('flex');
}

function editClient(clientId) {
    // Fetch client data and populate form
    fetch(`../api/users.php?id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Client';
            document.getElementById('formAction').value = 'update';
            document.getElementById('submitText').textContent = 'Update Client';
            document.getElementById('passwordField').classList.add('hidden');
            document.getElementById('statusField').classList.remove('hidden');
            document.getElementById('password').required = false;
            document.getElementById('username').readOnly = true;
            
            document.getElementById('userId').value = data.id;
            document.getElementById('username').value = data.username;
            document.getElementById('email').value = data.email;
            document.getElementById('first_name').value = data.first_name;
            document.getElementById('last_name').value = data.last_name;
            document.getElementById('company').value = data.company || '';
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('address').value = data.address || '';
            document.getElementById('status').value = data.status;
            
            document.getElementById('clientModal').classList.remove('hidden');
            document.getElementById('clientModal').classList.add('flex');
        })
        .catch(error => {
            alert('Error loading client data');
        });
}

function viewClient(clientId) {
    fetch(`../api/users.php?id=${clientId}`)
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                            <p>${new Date(data.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
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
                        <button onclick="editClient(${data.id}); closeViewModal();" class="vscode-button px-4 py-2 rounded text-sm">
                            <i class="fas fa-edit mr-2"></i>Edit Client
                        </button>
                        <button onclick="viewClientProjects(${data.id})" class="vscode-button-secondary px-4 py-2 rounded text-sm">
                            <i class="fas fa-project-diagram mr-2"></i>View Projects
                        </button>
                        <a href="mailto:${data.email}" class="vscode-button-secondary px-4 py-2 rounded text-sm">
                            <i class="fas fa-envelope mr-2"></i>Send Email
                        </a>
                    </div>
                </div>
            `;
            
            document.getElementById('clientDetailsContent').innerHTML = content;
            document.getElementById('viewClientModal').classList.remove('hidden');
            document.getElementById('viewClientModal').classList.add('flex');
        })
        .catch(error => {
            alert('Error loading client data');
        });
}

function closeViewModal() {
    document.getElementById('viewClientModal').classList.add('hidden');
    document.getElementById('viewClientModal').classList.remove('flex');
}

function viewClientProjects(clientId) {
    window.location.href = `projects.php?client=${clientId}`;
}

function deleteClient(clientId, clientName) {
    if (confirm(`Are you sure you want to delete ${clientName}? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="${clientId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function closeModal() {
    document.getElementById('clientModal').classList.add('hidden');
    document.getElementById('clientModal').classList.remove('flex');
}

// Close modal when clicking outside
document.getElementById('clientModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Close modal when clicking outside
document.getElementById('viewClientModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeViewModal();
    }
});
</script>
</body>
</html>

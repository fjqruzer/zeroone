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
            case 'respond':
                $inquiry_id = $_POST['inquiry_id'];
                $admin_response = trim($_POST['admin_response']);
                $status = $_POST['status'];
                
                $query = "UPDATE inquiries SET admin_response = :admin_response, status = :status, 
                         responded_by = :admin_id, responded_at = NOW() WHERE id = :inquiry_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':admin_response', $admin_response);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':admin_id', $_SESSION['user_id']);
                $stmt->bindParam(':inquiry_id', $inquiry_id);
                
                if ($stmt->execute()) {
                    $message = "Response sent successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to send response";
                    $message_type = "error";
                }
                break;
                
            case 'update_status':
                $inquiry_id = $_POST['inquiry_id'];
                $status = $_POST['status'];
                
                $query = "UPDATE inquiries SET status = :status WHERE id = :inquiry_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':inquiry_id', $inquiry_id);
                
                if ($stmt->execute()) {
                    $message = "Status updated successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to update status";
                    $message_type = "error";
                }
                break;
                
            case 'delete':
                $inquiry_id = $_POST['inquiry_id'];
                
                $query = "DELETE FROM inquiries WHERE id = :inquiry_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':inquiry_id', $inquiry_id);
                
                if ($stmt->execute()) {
                    $message = "Inquiry deleted successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete inquiry";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get all inquiries
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT i.*, u.first_name as admin_first_name, u.last_name as admin_last_name 
          FROM inquiries i 
          LEFT JOIN users u ON i.responded_by = u.id";

if ($filter !== 'all') {
    $query .= " WHERE i.status = :status";
}

$query .= " ORDER BY i.created_at DESC";

$stmt = $conn->prepare($query);
if ($filter !== 'all') {
    $stmt->bindParam(':status', $filter);
}
$stmt->execute();
$inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get inquiry counts for filters
$counts_query = "SELECT status, COUNT(*) as count FROM inquiries GROUP BY status";
$counts_stmt = $conn->prepare($counts_query);
$counts_stmt->execute();
$counts_data = $counts_stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = ['all' => 0, 'new' => 0, 'in_review' => 0, 'responded' => 0, 'closed' => 0];
foreach ($counts_data as $count) {
    $counts[$count['status']] = $count['count'];
    $counts['all'] += $count['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry Management - Zero One Labs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family-Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .filter-tab {
            transition: all 0.2s ease;
        }

        .filter-tab.active {
            background: var(--vscode-accent);
            color: white;
        }
    </style>
</head>
<body>
    <div class="min-h-screen p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">Inquiry Management</h1>
                <p class="text-sm" style="color: var(--vscode-text-muted);">Manage customer inquiries and project requests</p>
            </div>
            <div class="flex space-x-3">
                <a href="../dashboard-admin.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
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

        <!-- Filter Tabs -->
        <div class="mb-6">
            <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg w-fit">
                <a href="?filter=all" class="filter-tab px-4 py-2 rounded-md text-sm font-medium <?php echo $filter == 'all' ? 'active' : ''; ?>">
                    All (<?php echo $counts['all']; ?>)
                </a>
                <a href="?filter=new" class="filter-tab px-4 py-2 rounded-md text-sm font-medium <?php echo $filter == 'new' ? 'active' : ''; ?>">
                    New (<?php echo $counts['new']; ?>)
                </a>
                <a href="?filter=in_review" class="filter-tab px-4 py-2 rounded-md text-sm font-medium <?php echo $filter == 'in_review' ? 'active' : ''; ?>">
                    In Review (<?php echo $counts['in_review']; ?>)
                </a>
                <a href="?filter=responded" class="filter-tab px-4 py-2 rounded-md text-sm font-medium <?php echo $filter == 'responded' ? 'active' : ''; ?>">
                    Responded (<?php echo $counts['responded']; ?>)
                </a>
                <a href="?filter=closed" class="filter-tab px-4 py-2 rounded-md text-sm font-medium <?php echo $filter == 'closed' ? 'active' : ''; ?>">
                    Closed (<?php echo $counts['closed']; ?>)
                </a>
            </div>
        </div>

        <!-- Inquiries List -->
        <div class="space-y-4">
            <?php if (empty($inquiries)): ?>
            <div class="vscode-card rounded-lg p-12 text-center">
                <i class="fas fa-inbox text-4xl mb-4" style="color: var(--vscode-text-muted);"></i>
                <h3 class="text-lg font-medium mb-2">No inquiries found</h3>
                <p style="color: var(--vscode-text-muted);">No inquiries match the current filter</p>
            </div>
            <?php else: ?>
                <?php foreach ($inquiries as $inquiry): ?>
                <div class="vscode-card rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($inquiry['name']); ?></h3>
                                <?php
                                $status_colors = [
                                    'new' => 'bg-blue-100 text-blue-800',
                                    'in_review' => 'bg-yellow-100 text-yellow-800',
                                    'responded' => 'bg-green-100 text-green-800',
                                    'closed' => 'bg-gray-100 text-gray-800'
                                ];
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$inquiry['status']]; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $inquiry['status'])); ?>
                                </span>
                                <?php
                                $type_colors = [
                                    'general' => 'bg-gray-100 text-gray-800',
                                    'project' => 'bg-purple-100 text-purple-800',
                                    'support' => 'bg-orange-100 text-orange-800'
                                ];
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $type_colors[$inquiry['inquiry_type']]; ?>">
                                    <?php echo ucfirst($inquiry['inquiry_type']); ?>
                                </span>
                            </div>
                            <div class="text-sm space-y-1" style="color: var(--vscode-text-muted);">
                                <div><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($inquiry['email']); ?></div>
                                <?php if ($inquiry['phone']): ?>
                                <div><i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($inquiry['phone']); ?></div>
                                <?php endif; ?>
                                <?php if ($inquiry['company']): ?>
                                <div><i class="fas fa-building mr-2"></i><?php echo htmlspecialchars($inquiry['company']); ?></div>
                                <?php endif; ?>
                                <div><i class="fas fa-clock mr-2"></i><?php echo date('M j, Y g:i A', strtotime($inquiry['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="respondToInquiry(<?php echo $inquiry['id']; ?>)" class="text-blue-600 hover:text-blue-800 p-2" title="Respond">
                                <i class="fas fa-reply"></i>
                            </button>
                            <button onclick="updateInquiryStatus(<?php echo $inquiry['id']; ?>)" class="text-green-600 hover:text-green-800 p-2" title="Update Status">
                                <i class="fas fa-tasks"></i>
                            </button>
                            <button onclick="deleteInquiry(<?php echo $inquiry['id']; ?>)" class="text-red-600 hover:text-red-800 p-2" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <?php if ($inquiry['subject']): ?>
                    <div class="mb-3">
                        <strong>Subject:</strong> <?php echo htmlspecialchars($inquiry['subject']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <strong>Message:</strong>
                        <div class="mt-2 p-3 bg-gray-50 rounded border-l-4 border-blue-500">
                            <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($inquiry['admin_response']): ?>
                    <div class="border-t pt-4">
                        <div class="flex items-center justify-between mb-2">
                            <strong>Admin Response:</strong>
                            <div class="text-sm" style="color: var(--vscode-text-muted);">
                                by <?php echo htmlspecialchars($inquiry['admin_first_name'] . ' ' . $inquiry['admin_last_name']); ?>
                                on <?php echo date('M j, Y g:i A', strtotime($inquiry['responded_at'])); ?>
                            </div>
                        </div>
                        <div class="p-3 bg-green-50 rounded border-l-4 border-green-500">
                            <?php echo nl2br(htmlspecialchars($inquiry['admin_response'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-4xl h-96 flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold" id="responseModalTitle">Respond to Inquiry</h3>
                <button onclick="closeResponseModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Chat Messages Area -->
            <div class="flex-1 overflow-y-auto p-4 border rounded mb-4" id="adminChatMessages">
                <!-- Messages will be loaded here -->
            </div>
            
            <!-- Response Form -->
            <form id="responseForm" method="POST" class="flex space-x-2">
                <input type="hidden" name="action" value="respond">
                <input type="hidden" name="inquiry_id" id="responseInquiryId">
                <div class="flex-1">
                    <textarea name="admin_response" rows="3" required class="vscode-input w-full px-3 py-2 rounded" placeholder="Type your response..." id="adminResponseInput"></textarea>
                </div>
                <div class="flex flex-col space-y-2">
                    <select name="status" class="vscode-input px-3 py-2 rounded">
                        <option value="new">New</option>
                        <option value="in_review">In Review</option>
                        <option value="responded">Responded</option>
                        <option value="closed">Closed</option>
                    </select>
                    <button type="submit" class="vscode-button px-4 py-2 rounded">
                        <i class="fas fa-paper-plane mr-2"></i>Send
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Update Status</h3>
                <button onclick="closeStatusModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="inquiry_id" id="statusInquiryId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">New Status</label>
                        <select name="status" required class="vscode-input w-full px-3 py-2 rounded">
                            <option value="new">New</option>
                            <option value="in_review">In Review</option>
                            <option value="responded">Responded</option>
                            <option value="closed">Closed</option>
                        </select>
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

    <script>
        function respondToInquiry(inquiryId) {
            document.getElementById('responseInquiryId').value = inquiryId;
            document.getElementById('responseModal').classList.remove('hidden');
            document.getElementById('responseModal').classList.add('flex');
            
            // Load inquiry messages
            loadInquiryMessages(inquiryId);
        }

        function loadInquiryMessages(inquiryId) {
            fetch(`../api/messages.php?conversation_id=${inquiryId}&conversation_type=inquiry`)
            .then(response => response.json())
            .then(data => {
                const chatMessages = document.getElementById('adminChatMessages');
                
                if (data.messages && data.messages.length > 0) {
                    chatMessages.innerHTML = data.messages.map(msg => `
                        <div class="mb-4 ${msg.sender === 'client' ? 'text-left' : 'text-right'}">
                            <div class="inline-block max-w-xs lg:max-w-md p-3 rounded-lg ${msg.sender === 'client' ? 'bg-gray-100' : 'bg-blue-600 text-white'}">
                                <p class="text-sm">${msg.message}</p>
                                <p class="text-xs mt-1 opacity-70">${formatTimestamp(msg.timestamp)}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    // Fallback: load inquiry details
                    fetch(`../api/inquiries.php?id=${inquiryId}`)
                    .then(response => response.json())
                    .then(inquiryData => {
                        chatMessages.innerHTML = `
                            <div class="mb-4 text-left">
                                <div class="inline-block max-w-xs lg:max-w-md p-3 rounded-lg bg-gray-100">
                                    <p class="text-sm"><strong>Subject:</strong> ${inquiryData.subject || 'General Inquiry'}</p>
                                    <p class="text-sm mt-2">${inquiryData.message}</p>
                                    <p class="text-xs mt-1 opacity-70">${formatTimestamp(inquiryData.created_at)}</p>
                                </div>
                            </div>
                        `;
                    });
                }
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                document.getElementById('adminChatMessages').innerHTML = '<p class="text-red-500">Error loading messages</p>';
            });
        }

        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleString();
        }

        function updateInquiryStatus(inquiryId) {
            document.getElementById('statusInquiryId').value = inquiryId;
            document.getElementById('statusModal').classList.remove('hidden');
            document.getElementById('statusModal').classList.add('flex');
        }

        function deleteInquiry(inquiryId) {
            if (confirm('Are you sure you want to delete this inquiry? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="inquiry_id" value="${inquiryId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeResponseModal() {
            document.getElementById('responseModal').classList.add('hidden');
            document.getElementById('responseModal').classList.remove('flex');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
            document.getElementById('statusModal').classList.remove('flex');
        }

        // Close modals when clicking outside
        document.getElementById('responseModal').addEventListener('click', function(e) {
            if (e.target === this) closeResponseModal();
        });

        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) closeStatusModal();
        });
    </script>
</body>
</html>

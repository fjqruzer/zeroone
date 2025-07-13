<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

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
            case 'update_ticket':
                $ticket_id = $_POST['ticket_id'];
                $admin_response = trim($_POST['admin_response']);
                $status = $_POST['status'];
                
                $query = "UPDATE support_tickets SET admin_response = :admin_response, status = :status WHERE id = :ticket_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':admin_response', $admin_response);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':ticket_id', $ticket_id);
                
                if ($stmt->execute()) {
                    $message = "Support ticket updated successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to update support ticket";
                    $message_type = "error";
                }
                break;
                
            case 'delete_ticket':
                $ticket_id = $_POST['ticket_id'];
                $query = "DELETE FROM support_tickets WHERE id = :ticket_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':ticket_id', $ticket_id);
                
                if ($stmt->execute()) {
                    $message = "Support ticket deleted successfully";
                    $message_type = "success";
                } else {
                    $message = "Failed to delete support ticket";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Get support tickets with client information
$query = "SELECT st.*, u.first_name, u.last_name, u.email, u.company 
          FROM support_tickets st 
          JOIN users u ON st.client_id = u.id 
          ORDER BY st.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$stats = [
    'total' => count($tickets),
    'open' => count(array_filter($tickets, function($t) { return $t['status'] == 'open'; })),
    'in_progress' => count(array_filter($tickets, function($t) { return $t['status'] == 'in_progress'; })),
    'resolved' => count(array_filter($tickets, function($t) { return $t['status'] == 'resolved'; })),
    'closed' => count(array_filter($tickets, function($t) { return $t['status'] == 'closed'; }))
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
    <title>Support Management - Zero One Labs</title>
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

        .filter-tab {
            transition: all 0.2s ease;
        }

        .filter-tab.active {
            background: var(--vscode-accent);
            color: white;
        }

        .conversation-item {
            transition: all 0.2s ease;
        }

        .conversation-item:hover {
            background-color: var(--vscode-button-bg);
        }
    </style>
</head>
<body data-theme="light">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-headset text-blue-600 mr-3 text-xl"></i>
                    <h1 class="text-2xl font-bold">Support Management</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../dashboard-admin.php" class="vscode-button-secondary px-4 py-2 rounded">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                    <a href="?logout=1" class="text-red-500 hover:text-red-600">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </header>

        <!-- Message Display -->
        <?php if ($message): ?>
        <div class="mx-6 mt-4">
            <div class="p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Chat Interface -->
        <div class="flex h-screen" style="height: calc(100vh - 120px);">
            <!-- Conversations List -->
            <div class="w-1/3 border-r border-gray-200 flex flex-col">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Support Tickets</h2>
                        <button onclick="refreshTickets()" class="vscode-button-secondary px-3 py-1 rounded text-sm">
                            <i class="fas fa-refresh mr-1"></i>Refresh
                        </button>
                    </div>
                    <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg w-fit">
                        <button onclick="filterTickets('all')" class="filter-tab px-3 py-1 rounded-md text-sm font-medium active" id="filter-all">
                            All (<?php echo $stats['total']; ?>)
                        </button>
                        <button onclick="filterTickets('open')" class="filter-tab px-3 py-1 rounded-md text-sm font-medium" id="filter-open">
                            Open (<?php echo $stats['open']; ?>)
                        </button>
                        <button onclick="filterTickets('in_progress')" class="filter-tab px-3 py-1 rounded-md text-sm font-medium" id="filter-in-progress">
                            In Progress (<?php echo $stats['in_progress']; ?>)
                        </button>
                        <button onclick="filterTickets('resolved')" class="filter-tab px-3 py-1 rounded-md text-sm font-medium" id="filter-resolved">
                            Resolved (<?php echo $stats['resolved']; ?>)
                        </button>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto" id="conversationsList">
                    <!-- Conversations will be loaded here -->
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="flex-1 flex flex-col">
                <div id="chatHeader" class="p-4 border-b border-gray-200 bg-gray-50" style="display: none;">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold" id="chatTitle">Select a conversation</h3>
                            <p class="text-sm text-gray-600" id="chatSubtitle"></p>
                        </div>
                        <div class="flex items-center space-x-2" id="chatBadges">
                            <!-- Status and priority badges will be here -->
                        </div>
                    </div>
                </div>
                
                <div id="chatMessages" class="flex-1 overflow-y-auto p-4 bg-gray-50">
                    <div class="text-center text-gray-500 mt-8">
                        <i class="fas fa-comments text-4xl mb-4 opacity-50"></i>
                        <p>Select a conversation to start messaging</p>
                    </div>
                </div>
                
                <div id="chatInput" class="p-4 border-t border-gray-200 bg-white" style="display: none;">
                    <form onsubmit="sendAdminResponse(event)" class="flex space-x-3">
                        <div class="flex-1">
                            <textarea id="adminResponseInput" rows="3" required class="vscode-input w-full px-3 py-2 rounded" placeholder="Type your response..."></textarea>
                        </div>
                        <div class="flex flex-col space-y-2">
                            <select id="statusSelect" class="vscode-input px-3 py-2 rounded">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                            <button type="submit" class="vscode-button px-4 py-2 rounded">
                                <i class="fas fa-paper-plane mr-2"></i>Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticket View Modal -->
    <div id="ticketModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Support Ticket Details</h3>
                <button onclick="closeTicketModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="ticketModalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="vscode-card rounded-lg p-6 w-full max-w-4xl h-96 flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold" id="responseModalTitle">Respond to Ticket</h3>
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
                <input type="hidden" name="action" value="update_ticket">
                <input type="hidden" name="ticket_id" id="responseTicketId">
                <div class="flex-1">
                    <textarea name="admin_response" rows="3" required class="vscode-input w-full px-3 py-2 rounded" placeholder="Type your response..." id="adminResponseInput"></textarea>
                </div>
                <div class="flex flex-col space-y-2">
                    <select name="status" class="vscode-input px-3 py-2 rounded">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                    <button type="submit" class="vscode-button px-4 py-2 rounded">
                        <i class="fas fa-paper-plane mr-2"></i>Send
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentTicket = null;
        let allTickets = <?php echo json_encode($tickets); ?>;
        let filteredTickets = allTickets;

        // Load conversations on page load
        document.addEventListener('DOMContentLoaded', function() {
            displayConversations();
        });

        function displayConversations() {
            const conversationsList = document.getElementById('conversationsList');
            
            if (filteredTickets.length === 0) {
                conversationsList.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-inbox text-2xl mb-2 opacity-50"></i>
                        <p class="text-sm">No tickets found</p>
                    </div>
                `;
                return;
            }
            
            conversationsList.innerHTML = filteredTickets.map(ticket => `
                <div class="conversation-item p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors ${currentTicket?.id === ticket.id ? 'bg-blue-50 border-l-4 border-l-blue-500' : ''}" 
                     onclick="selectConversation(${ticket.id})">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-medium text-sm">${ticket.subject}</h4>
                        <div class="flex items-center space-x-1">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${getStatusColorJS(ticket.status)}">
                                ${ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1).replace('_', ' ')}
                            </span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColorJS(ticket.priority)}">
                                ${ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}
                            </span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-600 mb-2">${ticket.message.substring(0, 80)}${ticket.message.length > 80 ? '...' : ''}</p>
                    <div class="flex justify-between items-center text-xs text-gray-500">
                        <span>${ticket.first_name} ${ticket.last_name}</span>
                        <span>${formatTimestamp(ticket.created_at)}</span>
                    </div>
                </div>
            `).join('');
        }

        function selectConversation(ticketId) {
            currentTicket = allTickets.find(t => t.id === ticketId);
            if (!currentTicket) return;
            
            // Update UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('bg-blue-50', 'border-l-4', 'border-l-blue-500');
            });
            event.target.closest('.conversation-item').classList.add('bg-blue-50', 'border-l-4', 'border-l-blue-500');
            
            // Show chat interface
            document.getElementById('chatHeader').style.display = 'block';
            document.getElementById('chatInput').style.display = 'block';
            
            // Update chat header
            document.getElementById('chatTitle').textContent = currentTicket.subject;
            document.getElementById('chatSubtitle').textContent = `${currentTicket.first_name} ${currentTicket.last_name} • ${currentTicket.email}`;
            
            // Update badges
            document.getElementById('chatBadges').innerHTML = `
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${getStatusColorJS(currentTicket.status)}">
                    ${currentTicket.status.charAt(0).toUpperCase() + currentTicket.status.slice(1).replace('_', ' ')}
                </span>
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColorJS(currentTicket.priority)}">
                    ${currentTicket.priority.charAt(0).toUpperCase() + currentTicket.priority.slice(1)}
                </span>
            `;
            
            // Set current status in dropdown
            document.getElementById('statusSelect').value = currentTicket.status;
            
            // Load messages
            loadTicketMessages(ticketId);
        }

        function filterTickets(status) {
            // Update filter buttons
            document.querySelectorAll('.filter-tab').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`filter-${status.replace('_', '-')}`).classList.add('active');
            
            // Filter tickets
            if (status === 'all') {
                filteredTickets = allTickets;
            } else {
                filteredTickets = allTickets.filter(ticket => ticket.status === status);
            }
            
            // Reset current ticket if it's not in filtered results
            if (currentTicket && !filteredTickets.find(t => t.id === currentTicket.id)) {
                currentTicket = null;
                document.getElementById('chatHeader').style.display = 'none';
                document.getElementById('chatInput').style.display = 'none';
                document.getElementById('chatMessages').innerHTML = `
                    <div class="text-center text-gray-500 mt-8">
                        <i class="fas fa-comments text-4xl mb-4 opacity-50"></i>
                        <p>Select a conversation to start messaging</p>
                    </div>
                `;
            }
            
            displayConversations();
        }

        function loadTicketMessages(ticketId) {
            fetch(`../api/messages.php?conversation_id=${ticketId}&conversation_type=support`)
            .then(response => response.json())
            .then(data => {
                const chatMessages = document.getElementById('chatMessages');
                if (data.messages && data.messages.length > 0) {
                    chatMessages.innerHTML = data.messages.map(msg => `
                        <div class="mb-4 ${msg.sender_type === 'admin' ? 'text-right' : 'text-left'}">
                            <div class="inline-block max-w-xs lg:max-w-md p-3 rounded-lg ${msg.sender_type === 'admin' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200'}">
                                <p class="text-sm">${msg.message}</p>
                                <p class="text-xs mt-1 opacity-70">${formatTimestamp(msg.timestamp)}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    // Fallback: display basic ticket info
                    fetch(`../api/support.php?id=${ticketId}`)
                    .then(response => response.json())
                    .then(ticketData => {
                        chatMessages.innerHTML = `
                            <div class="mb-4 text-left">
                                <div class="inline-block max-w-xs lg:max-w-md p-3 rounded-lg bg-white border border-gray-200">
                                    <p class="text-sm"><strong>Subject:</strong> ${ticketData.subject}</p>
                                    <p class="text-sm mt-2">${ticketData.message}</p>
                                    <p class="text-xs mt-1 opacity-70">${formatTimestamp(ticketData.created_at)}</p>
                                </div>
                            </div>
                        `;
                    });
                }
                chatMessages.scrollTop = chatMessages.scrollHeight;
            })
            .catch(error => {
                console.error('Error loading messages:', error);
                document.getElementById('chatMessages').innerHTML = '<p class="text-red-500">Error loading messages</p>';
            });
        }

        function sendAdminResponse(event) {
            event.preventDefault();
            
            if (!currentTicket) return;
            
            const responseInput = document.getElementById('adminResponseInput');
            const statusSelect = document.getElementById('statusSelect');
            const message = responseInput.value.trim();
            const status = statusSelect.value;
            
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
            responseInput.value = '';
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Send to server using the messages API
            fetch('../api/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: currentTicket.id,
                    conversation_type: 'support',
                    message: message,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update ticket in local array
                    const ticketIndex = allTickets.findIndex(t => t.id === currentTicket.id);
                    if (ticketIndex !== -1) {
                        allTickets[ticketIndex].status = status;
                        allTickets[ticketIndex].admin_response = message;
                        currentTicket = allTickets[ticketIndex];
                    }
                    
                    // Refresh conversations
                    displayConversations();
                    
                    // Update badges
                    document.getElementById('chatBadges').innerHTML = `
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${getStatusColorJS(status)}">
                            ${status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')}
                        </span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${getPriorityColorJS(currentTicket.priority)}">
                            ${currentTicket.priority.charAt(0).toUpperCase() + currentTicket.priority.slice(1)}
                        </span>
                    `;
                    
                    // Reload messages to show the complete conversation
                    loadTicketMessages(currentTicket.id);
                } else {
                    throw new Error(data.error || 'Failed to send response');
                }
            })
            .catch(error => {
                console.error('Error sending response:', error);
                alert('Error sending response: ' + error.message);
                
                // Remove the optimistic message
                const lastMessage = chatMessages.lastElementChild;
                if (lastMessage) {
                    lastMessage.remove();
                }
            });
        }

        function getStatusColorJS(status) {
            const colors = {
                'open': 'bg-yellow-100 text-yellow-800',
                'in_progress': 'bg-blue-100 text-blue-800',
                'resolved': 'bg-green-100 text-green-800',
                'closed': 'bg-gray-100 text-gray-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }

        function getPriorityColorJS(priority) {
            const colors = {
                'low': 'bg-gray-100 text-gray-800',
                'medium': 'bg-yellow-100 text-yellow-800',
                'high': 'bg-orange-100 text-orange-800',
                'urgent': 'bg-red-100 text-red-800'
            };
            return colors[priority] || 'bg-gray-100 text-gray-800';
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

        // Legacy functions for backward compatibility
        function viewTicket(ticketId) {
            selectConversation(ticketId);
        }

        function respondToTicket(ticketId) {
            selectConversation(ticketId);
        }

        function closeTicketModal() {
            document.getElementById('ticketModal').classList.add('hidden');
            document.getElementById('ticketModal').classList.remove('flex');
        }

        function closeResponseModal() {
            document.getElementById('responseModal').classList.add('hidden');
            document.getElementById('responseModal').classList.remove('flex');
        }

        // Close modals when clicking outside
        document.getElementById('ticketModal').addEventListener('click', function(e) {
            if (e.target === this) closeTicketModal();
        });

        document.getElementById('responseModal').addEventListener('click', function(e) {
            if (e.target === this) closeResponseModal();
        });

        function refreshTickets() {
            // Reload all tickets from the server
            fetch('../api/support.php?action=get_all_tickets')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allTickets = data.tickets;
                        filteredTickets = allTickets; // Reset filter to all tickets
                        displayConversations();
                        document.querySelectorAll('.filter-tab').forEach(btn => btn.classList.remove('active'));
                        document.getElementById('filter-all').classList.add('active');
                        document.getElementById('chatMessages').innerHTML = `
                            <div class="text-center text-gray-500 mt-8">
                                <i class="fas fa-comments text-4xl mb-4 opacity-50"></i>
                                <p>Select a conversation to start messaging</p>
                            </div>
                        `;
                        currentTicket = null;
                        document.getElementById('chatHeader').style.display = 'none';
                        document.getElementById('chatInput').style.display = 'none';
                    } else {
                        alert('Failed to refresh tickets: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error refreshing tickets:', error);
                    alert('Error refreshing tickets: ' + error.message);
                });
        }
    </script>
</body>
</html>

<?php
function getPriorityColor($priority) {
    $colors = [
        'low' => 'bg-gray-100 text-gray-800',
        'medium' => 'bg-yellow-100 text-yellow-800',
        'high' => 'bg-orange-100 text-orange-800',
        'urgent' => 'bg-red-100 text-red-800'
    ];
    return $colors[$priority] ?? 'bg-gray-100 text-gray-800';
}

function getStatusColor($status) {
    $colors = [
        'open' => 'bg-yellow-100 text-yellow-800',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'resolved' => 'bg-green-100 text-green-800',
        'closed' => 'bg-gray-100 text-gray-800'
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-800';
}
?> 
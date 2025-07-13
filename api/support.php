<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
$database = new Database();
$conn = $database->getConnection();

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get support tickets or individual ticket
        if (isset($_GET['id'])) {
            // Get specific ticket with messages
            $ticket_id = $_GET['id'];
            
            // Check permissions
            if ($user_role === 'admin') {
                // Admin can view any ticket
                $query = "SELECT st.*, u.first_name, u.last_name, u.email, u.company 
                         FROM support_tickets st 
                         JOIN users u ON st.client_id = u.id 
                         WHERE st.id = :ticket_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':ticket_id', $ticket_id);
            } else {
                // Clients can only view their own tickets
                $query = "SELECT st.*, u.first_name, u.last_name, u.email, u.company 
                         FROM support_tickets st 
                         JOIN users u ON st.client_id = u.id 
                         WHERE st.id = :ticket_id AND st.client_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':ticket_id', $ticket_id);
                $stmt->bindParam(':user_id', $user_id);
            }
            
            $stmt->execute();
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ticket) {
                // Parse messages from the ticket
                $messages = parseSupportTicketMessages($ticket);
                $ticket['messages'] = $messages;
                echo json_encode($ticket);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket not found']);
            }
        } else {
            // Get all tickets (filtered by role)
            if ($user_role === 'admin') {
                // Admin can see all tickets
                $query = "SELECT st.*, u.first_name, u.last_name, u.email, u.company 
                         FROM support_tickets st 
                         JOIN users u ON st.client_id = u.id 
                         ORDER BY st.created_at DESC";
                $stmt = $conn->prepare($query);
            } else {
                // Clients can only see their own tickets
                $query = "SELECT * FROM support_tickets WHERE client_id = :user_id ORDER BY created_at DESC";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
            }
            
            $stmt->execute();
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['tickets' => $tickets]);
        }
        break;
        
    case 'POST':
        // Create new support ticket
        if ($user_role !== 'client') {
            http_response_code(403);
            echo json_encode(['error' => 'Only clients can create support tickets']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['subject']) || !isset($data['message'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Subject and message are required']);
            exit;
        }
        
        $subject = trim($data['subject']);
        $message = trim($data['message']);
        $priority = isset($data['priority']) ? $data['priority'] : 'medium';
        
        if (empty($subject) || empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Subject and message cannot be empty']);
            exit;
        }
        
        $query = "INSERT INTO support_tickets (client_id, subject, message, priority) VALUES (:client_id, :subject, :message, :priority)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':client_id', $user_id);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':priority', $priority);
        
        if ($stmt->execute()) {
            $ticket_id = $conn->lastInsertId();
            echo json_encode([
                'success' => true,
                'message' => 'Support ticket created successfully',
                'ticket_id' => $ticket_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create support ticket']);
        }
        break;
        
    case 'PUT':
        // Update support ticket (admin response)
        if ($user_role !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Only admins can update support tickets']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['ticket_id']) || !isset($data['admin_response']) || !isset($data['status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ticket ID, admin response, and status are required']);
            exit;
        }
        
        $ticket_id = $data['ticket_id'];
        $admin_response = trim($data['admin_response']);
        $status = $data['status'];
        
        $query = "UPDATE support_tickets SET admin_response = :admin_response, status = :status WHERE id = :ticket_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':admin_response', $admin_response);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':ticket_id', $ticket_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Support ticket updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update support ticket']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function parseSupportTicketMessages($ticket) {
    $messages = [];
    
    // Parse the main message field for multiple messages
    $messageText = $ticket['message'];
    
    if (strpos($messageText, '--- New Message ---') !== false) {
        // Split by the separator
        $parts = explode('--- New Message ---', $messageText);
        
        // First part is the original message (from client)
        if (!empty(trim($parts[0]))) {
            $messages[] = [
                'sender' => 'client',
                'message' => trim($parts[0]),
                'timestamp' => $ticket['created_at']
            ];
        }
        
        // Additional parts are admin responses (since only admins can add messages)
        for ($i = 1; $i < count($parts); $i++) {
            if (!empty(trim($parts[$i]))) {
                $messages[] = [
                    'sender' => 'admin',
                    'message' => trim($parts[$i]),
                    'timestamp' => $ticket['responded_at'] ?? $ticket['updated_at']
                ];
            }
        }
    } else {
        // Single message (from client)
        $messages[] = [
            'sender' => 'client',
            'message' => $messageText,
            'timestamp' => $ticket['created_at']
        ];
    }
    
    // Add admin response if exists and is different from the last message
    // This handles the case where admin_response is stored separately
    if (!empty($ticket['admin_response'])) {
        $lastMessage = end($messages);
        if ($lastMessage && $lastMessage['message'] !== $ticket['admin_response']) {
            $messages[] = [
                'sender' => 'admin',
                'message' => $ticket['admin_response'],
                'timestamp' => $ticket['responded_at'] ?? $ticket['updated_at']
            ];
        }
    }
    
    return $messages;
}
?> 
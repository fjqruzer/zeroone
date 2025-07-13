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

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['conversation_id']) || !isset($data['message']) || !isset($data['conversation_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Conversation ID, message, and conversation type are required']);
            exit;
        }
        $conversation_id = $data['conversation_id'];
        $message = trim($data['message']);
        $conversation_type = $data['conversation_type'];
        $status = isset($data['status']) ? $data['status'] : 'open';
        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Message cannot be empty']);
            exit;
        }
        $sender_type = $user_role === 'admin' ? 'admin' : 'client';
        try {
            // Insert message
            $query = "INSERT INTO messages (conversation_type, conversation_id, sender_id, sender_type, message) VALUES (:type, :cid, :sid, :stype, :msg)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':type', $conversation_type);
            $stmt->bindParam(':cid', $conversation_id);
            $stmt->bindParam(':sid', $user_id);
            $stmt->bindParam(':stype', $sender_type);
            $stmt->bindParam(':msg', $message);
            $stmt->execute();

            // Update status and responded_by/responded_at if admin
            if ($conversation_type === 'support') {
                if ($user_role === 'admin') {
                    $query = "UPDATE support_tickets SET status = :status, responded_by = :admin_id, responded_at = NOW(), admin_response = :msg WHERE id = :cid";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':admin_id', $user_id);
                    $stmt->bindParam(':msg', $message);
                    $stmt->bindParam(':cid', $conversation_id);
                    $stmt->execute();
                } else {
                    $query = "UPDATE support_tickets SET status = :status WHERE id = :cid";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':cid', $conversation_id);
                    $stmt->execute();
                }
            } elseif ($conversation_type === 'inquiry') {
                if ($user_role === 'admin') {
                    $query = "UPDATE inquiries SET status = :status, responded_by = :admin_id, responded_at = NOW(), admin_response = :msg WHERE id = :cid";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':admin_id', $user_id);
                    $stmt->bindParam(':msg', $message);
                    $stmt->bindParam(':cid', $conversation_id);
                    $stmt->execute();
                } else {
                    $query = "UPDATE inquiries SET status = :status WHERE id = :cid";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':cid', $conversation_id);
                    $stmt->execute();
                }
            }
            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Messages API Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
    case 'GET':
        if (!isset($_GET['conversation_id']) || !isset($_GET['conversation_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Conversation ID and type are required']);
            exit;
        }
        $conversation_id = $_GET['conversation_id'];
        $conversation_type = $_GET['conversation_type'];
        try {
            $query = "SELECT sender_type, message, created_at as timestamp FROM messages WHERE conversation_type = :type AND conversation_id = :cid ORDER BY created_at ASC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':type', $conversation_type);
            $stmt->bindParam(':cid', $conversation_id);
            $stmt->execute();
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['messages' => $messages]);
        } catch (Exception $e) {
            error_log("Messages API GET Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
} 
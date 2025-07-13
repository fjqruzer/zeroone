<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get specific user
            $user_id = $_GET['id'];
            $query = "SELECT id, username, email, role, first_name, last_name, company, phone, status, created_at FROM users WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                echo json_encode($user);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
            }
        } else {
            // Get all users
            $status = $_GET['status'] ?? null;
            $role = $_GET['role'] ?? null;
            
            $query = "SELECT id, username, email, role, first_name, last_name, company, phone, status, created_at FROM users WHERE 1=1";
            $params = [];
            
            if ($status) {
                $query .= " AND status = :status";
                $params[':status'] = $status;
            }
            
            if ($role) {
                $query .= " AND role = :role";
                $params[':role'] = $role;
            }
            
            $query .= " ORDER BY created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
        }
        break;
        
    case 'PUT':
        // Update user
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $input['id'] ?? null;
        
        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            exit();
        }
        
        $fields = [];
        $params = [':id' => $user_id];
        
        if (isset($input['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $input['status'];
        }
        
        if (isset($input['role'])) {
            $fields[] = "role = :role";
            $params[':role'] = $input['role'];
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit();
        }
        
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update user']);
        }
        break;
        
    case 'DELETE':
        // Delete user
        $user_id = $_GET['id'] ?? null;
        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            exit();
        }
        
        // Don't allow deleting the current admin user
        if ($user_id == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete your own account']);
            exit();
        }
        
        $query = "DELETE FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete user']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>

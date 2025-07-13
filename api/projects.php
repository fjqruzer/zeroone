<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get specific project
            $project_id = $_GET['id'];
            $include_history = isset($_GET['history']) && $_GET['history'] === 'true';
            
            $query = "SELECT p.*, u.first_name, u.last_name, u.company, u.email, u.phone 
                     FROM projects p 
                     JOIN users u ON p.client_id = u.id 
                     WHERE p.id = :project_id";
            
            if ($user_role === 'client') {
                $query .= " AND p.client_id = :user_id";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':project_id', $project_id);
            if ($user_role === 'client') {
                $stmt->bindParam(':user_id', $user_id);
            }
            $stmt->execute();
            
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($project) {
                // Get status history if requested
                if ($include_history) {
                    $history_query = "SELECT h.*, u.first_name, u.last_name 
                                     FROM project_status_history h 
                                     LEFT JOIN users u ON h.admin_id = u.id 
                                     WHERE h.project_id = :project_id 
                                     ORDER BY h.changed_at DESC";
                    $history_stmt = $conn->prepare($history_query);
                    $history_stmt->bindParam(':project_id', $project_id);
                    $history_stmt->execute();
                    $project['status_history'] = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                echo json_encode($project);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Project not found']);
            }
        } else {
            // Get all projects
            $query = "SELECT p.*, u.first_name, u.last_name, u.company 
                     FROM projects p 
                     JOIN users u ON p.client_id = u.id";
            
            if ($user_role === 'client') {
                $query .= " WHERE p.client_id = :user_id";
            }
            
            $query .= " ORDER BY p.created_at DESC";
            
            $stmt = $conn->prepare($query);
            if ($user_role === 'client') {
                $stmt->bindParam(':user_id', $user_id);
            }
            $stmt->execute();
            
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($projects);
        }
        break;
        
    case 'POST':
        // Create new project (clients only)
        if ($user_role !== 'client') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $project_name = trim($input['project_name'] ?? '');
        $description = trim($input['description'] ?? '');
        $budget = $input['budget'] ?? null;
        $github_repo = trim($input['github_repo'] ?? '');
        
        if (empty($project_name) || empty($description)) {
            http_response_code(400);
            echo json_encode(['error' => 'Project name and description are required']);
            exit();
        }
        
        $query = "INSERT INTO projects (client_id, project_name, description, budget, github_repo, status) 
                 VALUES (:client_id, :project_name, :description, :budget, :github_repo, 'pending')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':client_id', $user_id);
        $stmt->bindParam(':project_name', $project_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':budget', $budget);
        $stmt->bindParam(':github_repo', $github_repo);
        
        if ($stmt->execute()) {
            $project_id = $conn->lastInsertId();
            echo json_encode(['success' => true, 'project_id' => $project_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create project']);
        }
        break;
        
    case 'PUT':
        // Update project (admin only)
        if ($user_role !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $project_id = $input['id'] ?? null;
        
        if (!$project_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Project ID is required']);
            exit();
        }
        
        $fields = [];
        $params = [':id' => $project_id];
        
        if (isset($input['status'])) {
            $fields[] = "status = :status";
            $params[':status'] = $input['status'];
        }
        
        if (isset($input['budget'])) {
            $fields[] = "budget = :budget";
            $params[':budget'] = $input['budget'];
        }
        
        if (isset($input['start_date'])) {
            $fields[] = "start_date = :start_date";
            $params[':start_date'] = $input['start_date'];
        }
        
        if (isset($input['end_date'])) {
            $fields[] = "end_date = :end_date";
            $params[':end_date'] = $input['end_date'];
        }
        
        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit();
        }
        
        $query = "UPDATE projects SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $conn->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update project']);
        }
        break;
        
    case 'DELETE':
        // Delete project (admin only)
        if ($user_role !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit();
        }
        
        $project_id = $_GET['id'] ?? null;
        if (!$project_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Project ID is required']);
            exit();
        }
        
        $query = "DELETE FROM projects WHERE id = :project_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete project']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>

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

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$stats = [];

if ($user_role === 'admin') {
    // Admin statistics
    $queries = [
        'total_clients' => "SELECT COUNT(*) as count FROM users WHERE role = 'client'",
        'total_projects' => "SELECT COUNT(*) as count FROM projects",
        'active_projects' => "SELECT COUNT(*) as count FROM projects WHERE status = 'in_progress'",
        'completed_projects' => "SELECT COUNT(*) as count FROM projects WHERE status = 'completed'",
        'pending_approvals' => "SELECT COUNT(*) as count FROM users WHERE status = 'pending'",
        'total_revenue' => "SELECT COALESCE(SUM(budget), 0) as revenue FROM projects WHERE status = 'completed'"
    ];
    
    foreach ($queries as $key => $query) {
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats[$key] = $result['count'] ?? $result['revenue'] ?? 0;
    }
    
    // Monthly project creation
    $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
              FROM projects 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['monthly_projects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Project status distribution
    $query = "SELECT status, COUNT(*) as count FROM projects GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['project_status_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} else {
    // Client statistics
    $queries = [
        'total_projects' => "SELECT COUNT(*) as count FROM projects WHERE client_id = :user_id",
        'active_projects' => "SELECT COUNT(*) as count FROM projects WHERE client_id = :user_id AND status = 'in_progress'",
        'completed_projects' => "SELECT COUNT(*) as count FROM projects WHERE client_id = :user_id AND status = 'completed'",
        'pending_projects' => "SELECT COUNT(*) as count FROM projects WHERE client_id = :user_id AND status = 'pending'",
        'total_budget' => "SELECT COALESCE(SUM(budget), 0) as budget FROM projects WHERE client_id = :user_id"
    ];
    
    foreach ($queries as $key => $query) {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats[$key] = $result['count'] ?? $result['budget'] ?? 0;
    }
    
    // Client's project timeline
    $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
              FROM projects 
              WHERE client_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $stats['project_timeline'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($stats);
?>

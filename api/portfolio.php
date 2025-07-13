<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $category = $_GET['category'] ?? null;
    $featured = $_GET['featured'] ?? null;
    $limit = $_GET['limit'] ?? null;
    
    $query = "SELECT * FROM portfolio_projects WHERE status = 'active'";
    $params = [];
    
    if ($category) {
        $query .= " AND category = :category";
        $params[':category'] = $category;
    }
    
    if ($featured !== null) {
        $query .= " AND featured = :featured";
        $params[':featured'] = $featured === 'true' ? 1 : 0;
    }
    
    $query .= " ORDER BY display_order, title";
    
    if ($limit) {
        $query .= " LIMIT :limit";
        $params[':limit'] = (int)$limit;
    }
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        if ($key === ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $portfolio_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process technologies for each project
    foreach ($portfolio_projects as &$project) {
        if ($project['technologies']) {
            $project['technologies'] = array_map('trim', explode(',', $project['technologies']));
        } else {
            $project['technologies'] = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $portfolio_projects
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 
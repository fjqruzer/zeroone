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
    
    $query = "SELECT * FROM services WHERE status = 'active'";
    $params = [];
    
    if ($category) {
        $query .= " AND category = :category";
        $params[':category'] = $category;
    }
    
    if ($featured !== null) {
        $query .= " AND featured = :featured";
        $params[':featured'] = $featured === 'true' ? 1 : 0;
    }
    
    $query .= " ORDER BY display_order, service_name";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decode features JSON for each service
    foreach ($services as &$service) {
        if ($service['features']) {
            $service['features'] = json_decode($service['features'], true);
        } else {
            $service['features'] = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $services
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 
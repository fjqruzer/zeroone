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
    $section = $_GET['section'] ?? null;
    
    if ($section) {
        // Get specific section
        $query = "SELECT * FROM company_info WHERE section = :section AND status = 'active' ORDER BY display_order";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':section', $section);
    } else {
        // Get all active sections
        $query = "SELECT * FROM company_info WHERE status = 'active' ORDER BY display_order, section";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $company_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by section
    $grouped_info = [];
    foreach ($company_info as $info) {
        $grouped_info[$info['section']][] = $info;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $grouped_info
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 
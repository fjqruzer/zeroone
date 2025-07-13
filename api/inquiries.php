<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$database = new Database();
$conn = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get user-specific inquiries or individual inquiry
    if (isset($_GET['id'])) {
        // Get specific inquiry (admin only)
        $inquiry_id = $_GET['id'];
        $query = "SELECT * FROM inquiries WHERE id = :inquiry_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':inquiry_id', $inquiry_id);
        $stmt->execute();
        $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inquiry) {
            echo json_encode($inquiry);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Inquiry not found']);
        }
    } elseif (isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];
        
        // Get user's email to find their inquiries
        $query = "SELECT email FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $query = "SELECT * FROM inquiries WHERE email = :email ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':email', $user['email']);
            $stmt->execute();
            $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['inquiries' => $inquiries]);
        } else {
            echo json_encode(['inquiries' => []]);
        }
    } else {
        echo json_encode(['error' => 'User ID or inquiry ID required']);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['name', 'email', 'message'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            exit();
        }
    }
    
    // Validate email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit();
    }
    
    $name = trim($input['name']);
    $email = trim($input['email']);
    $phone = trim($input['phone'] ?? '');
    $company = trim($input['company'] ?? '');
    $inquiry_type = trim($input['inquiry_type'] ?? 'general');
    $subject = trim($input['subject'] ?? '');
    $message = trim($input['message']);
    
    // Validate inquiry type
    $valid_types = ['general', 'project', 'support'];
    if (!in_array($inquiry_type, $valid_types)) {
        $inquiry_type = 'general';
    }
    
    try {
        $query = "INSERT INTO inquiries (name, email, phone, company, inquiry_type, subject, message) 
                 VALUES (:name, :email, :phone, :company, :inquiry_type, :subject, :message)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':company', $company);
        $stmt->bindParam(':inquiry_type', $inquiry_type);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        
        if ($stmt->execute()) {
            $inquiry_id = $conn->lastInsertId();
            
            // Send email notification (placeholder for now)
            // In a real implementation, you would send an email to admin
            // mail('admin@zeroonelabs.com', 'New Inquiry: ' . $subject, $message);
            
            echo json_encode([
                'success' => true,
                'message' => 'Inquiry submitted successfully',
                'inquiry_id' => $inquiry_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to submit inquiry']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?> 
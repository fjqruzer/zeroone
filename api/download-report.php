<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('admin');

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

$database = new Database();
$conn = $database->getConnection();

if ($type === 'project' && $id) {
    // Generate project report
    $query = "SELECT p.*, u.first_name, u.last_name, u.email, u.company FROM projects p 
              JOIN users u ON p.client_id = u.id WHERE p.id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="project-' . $id . '-report.pdf"');
        
        // Simple text-based report (in a real application, you'd use a PDF library)
        $report = "PROJECT REPORT\n\n";
        $report .= "Project: " . $project['project_name'] . "\n";
        $report .= "Client: " . $project['first_name'] . " " . $project['last_name'] . "\n";
        $report .= "Company: " . ($project['company'] ?: 'N/A') . "\n";
        $report .= "Email: " . $project['email'] . "\n";
        $report .= "Status: " . ucfirst(str_replace('_', ' ', $project['status'])) . "\n";
        $report .= "Budget: $" . ($project['budget'] ? number_format($project['budget']) : 'TBD') . "\n";
        $report .= "Created: " . date('F j, Y', strtotime($project['created_at'])) . "\n\n";
        $report .= "Description:\n" . $project['description'] . "\n\n";
        if ($project['github_repo']) {
            $report .= "Repository: " . $project['github_repo'] . "\n";
        }
        if ($project['deployment_url']) {
            $report .= "Live URL: " . $project['deployment_url'] . "\n";
        }
        
        echo $report;
    }
} elseif ($type === 'system') {
    // Generate system report
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="system-report-' . date('Y-m-d') . '.txt"');
    
    // Get system statistics
    $stats = [];
    
    $query = "SELECT COUNT(*) as count FROM users WHERE role = 'client'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $query = "SELECT COUNT(*) as count FROM projects";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $query = "SELECT COUNT(*) as count FROM projects WHERE status = 'completed'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['completed'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $query = "SELECT COALESCE(SUM(budget), 0) as revenue FROM projects WHERE status = 'completed'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
    
    $report = "ZERO ONE LABS - SYSTEM REPORT\n";
    $report .= "Generated: " . date('F j, Y g:i A') . "\n\n";
    $report .= "STATISTICS:\n";
    $report .= "Total Clients: " . $stats['clients'] . "\n";
    $report .= "Total Projects: " . $stats['projects'] . "\n";
    $report .= "Completed Projects: " . $stats['completed'] . "\n";
    $report .= "Total Revenue: $" . number_format($stats['revenue']) . "\n\n";
    
    echo $report;
}
?>

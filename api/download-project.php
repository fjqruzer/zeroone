<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    exit('Unauthorized');
}

$project_id = $_GET['id'] ?? '';
$database = new Database();
$conn = $database->getConnection();

// Verify project access
if ($_SESSION['role'] === 'client') {
    $query = "SELECT * FROM projects WHERE id = :id AND client_id = :client_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $project_id);
    $stmt->bindParam(':client_id', $_SESSION['user_id']);
} else {
    $query = "SELECT * FROM projects WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $project_id);
}

$stmt->execute();
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if ($project) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="project-' . $project_id . '-files.zip"');
    
    // Create a simple text file as project deliverable
    $content = "PROJECT FILES FOR: " . $project['project_name'] . "\n\n";
    $content .= "This is a placeholder for actual project files.\n";
    $content .= "In a real implementation, this would contain:\n";
    $content .= "- Source code files\n";
    $content .= "- Documentation\n";
    $content .= "- Assets and resources\n";
    $content .= "- Build files\n\n";
    $content .= "Project Details:\n";
    $content .= "Status: " . ucfirst(str_replace('_', ' ', $project['status'])) . "\n";
    $content .= "Created: " . date('F j, Y', strtotime($project['created_at'])) . "\n";
    if ($project['github_repo']) {
        $content .= "Repository: " . $project['github_repo'] . "\n";
    }
    
    echo $content;
} else {
    http_response_code(404);
    echo "Project not found";
}
?>

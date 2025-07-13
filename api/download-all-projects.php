<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('client');

$database = new Database();
$conn = $database->getConnection();

// Get all user's projects
$query = "SELECT * FROM projects WHERE client_id = :client_id ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':client_id', $_SESSION['user_id']);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="all-projects-' . date('Y-m-d') . '.zip"');

$content = "ALL PROJECTS ARCHIVE\n";
$content .= "Client: " . $_SESSION['full_name'] . "\n";
$content .= "Generated: " . date('F j, Y g:i A') . "\n\n";

foreach ($projects as $project) {
    $content .= "PROJECT: " . $project['project_name'] . "\n";
    $content .= "Status: " . ucfirst(str_replace('_', ' ', $project['status'])) . "\n";
    $content .= "Budget: $" . ($project['budget'] ? number_format($project['budget']) : 'TBD') . "\n";
    $content .= "Created: " . date('F j, Y', strtotime($project['created_at'])) . "\n";
    $content .= "Description: " . $project['description'] . "\n";
    if ($project['github_repo']) {
        $content .= "Repository: " . $project['github_repo'] . "\n";
    }
    $content .= "\n" . str_repeat("-", 50) . "\n\n";
}

echo $content;
?>

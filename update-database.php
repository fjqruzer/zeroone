<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Updating database schema...\n";

try {
    // Create support_tickets table
    $query = "CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        admin_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    echo "✓ Support tickets table created successfully\n";
    
    // Create inquiries table
    $query = "CREATE TABLE IF NOT EXISTS inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        company VARCHAR(100),
        inquiry_type ENUM('general', 'project', 'support') DEFAULT 'general',
        subject VARCHAR(200),
        message TEXT NOT NULL,
        status ENUM('new', 'in_review', 'responded', 'closed') DEFAULT 'new',
        admin_response TEXT,
        responded_by INT,
        responded_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    echo "✓ Inquiries table created successfully\n";
    
    // Insert sample support tickets if table is empty
    $query = "SELECT COUNT(*) as count FROM support_tickets";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        // Get a client user ID
        $query = "SELECT id FROM users WHERE role = 'client' LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            $query = "INSERT INTO support_tickets (client_id, subject, message, priority, status) VALUES 
                     (:client_id, 'Project Update Request', 'I would like to know the current status of my e-commerce platform project.', 'medium', 'open'),
                     (:client_id, 'Feature Request', 'Can we add a payment gateway integration to the portfolio website?', 'low', 'in_progress')";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':client_id', $client['id']);
            $stmt->execute();
            echo "✓ Sample support tickets added\n";
        }
    }
    
    // Insert sample inquiries if table is empty
    $query = "SELECT COUNT(*) as count FROM inquiries";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        $query = "INSERT INTO inquiries (name, email, phone, company, inquiry_type, subject, message, status) VALUES 
                 ('John Smith', 'john@example.com', '+1-555-0123', 'Tech Corp', 'project', 'Website Development Request', 'I am interested in having a new website developed for my company. We need a modern, responsive design with e-commerce functionality.', 'new'),
                 ('Sarah Johnson', 'sarah@startup.com', '+1-555-0456', 'Startup Inc', 'general', 'General Inquiry', 'I would like to learn more about your services and pricing structure.', 'in_review'),
                 ('Mike Wilson', 'mike@business.com', '+1-555-0789', 'Business Solutions', 'support', 'Technical Support', 'I need help with my existing website maintenance and updates.', 'responded')";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        echo "✓ Sample inquiries added\n";
    }
    
    echo "\nDatabase update completed successfully!\n";
    echo "Support and Inquiries functionality is now available.\n";
    
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?> 
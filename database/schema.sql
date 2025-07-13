-- Create database
CREATE DATABASE IF NOT EXISTS zero_one_labs;
USE zero_one_labs;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') DEFAULT 'client',
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    company VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    project_name VARCHAR(100) NOT NULL,
    description TEXT,
    github_repo VARCHAR(255),
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    budget DECIMAL(10,2),
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Inquiries table (General contact and project inquiries)
CREATE TABLE IF NOT EXISTS inquiries (
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
);

-- Support tickets table
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    admin_response TEXT,
    responded_by INT,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Sessions table for security
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Delete existing demo users first
DELETE FROM users WHERE username IN ('admin', 'johndoe');

-- Insert default admin user with properly hashed password (admin123)
INSERT INTO users (username, email, password, role, first_name, last_name, status) 
VALUES ('admin', 'admin@zeroonelabs.com', '$2y$10$YourHashedPasswordHere', 'admin', 'Admin', 'User', 'active');

-- Insert sample client with properly hashed password (client123)  
INSERT INTO users (username, email, password, role, first_name, last_name, company, status) 
VALUES ('johndoe', 'john@example.com', '$2y$10$YourHashedPasswordHere', 'client', 'John', 'Doe', 'Tech Corp', 'active');

-- Note: Run fix-passwords.php after setting up the database to generate proper hashes

-- Insert sample projects
INSERT INTO projects (client_id, project_name, description, status, github_repo) VALUES
(2, 'E-commerce Platform', 'Modern e-commerce solution with React and Node.js', 'in_progress', 'https://github.com/zeroonelabs/ecommerce-platform'),
(2, 'Portfolio Website', 'Personal portfolio website with modern design', 'completed', 'https://github.com/zeroonelabs/portfolio-site'),
(2, 'Mobile App Backend', 'REST API backend for mobile application', 'pending', NULL);

-- Insert sample support tickets
INSERT INTO support_tickets (client_id, subject, message, priority, status) VALUES
(2, 'Project Update Request', 'I would like to know the current status of my e-commerce platform project.', 'medium', 'open'),
(2, 'Feature Request', 'Can we add a payment gateway integration to the portfolio website?', 'low', 'in_progress');

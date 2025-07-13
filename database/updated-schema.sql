-- Updated database schema based on SRS requirements
CREATE DATABASE IF NOT EXISTS zero_one_labs;
USE zero_one_labs;

-- Users table (Admin and Client management)
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
    address TEXT,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Projects table (Enhanced for SRS requirements)
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    project_name VARCHAR(100) NOT NULL,
    description TEXT,
    requirements TEXT,
    github_repo VARCHAR(255),
    deployment_url VARCHAR(255),
    status ENUM('pending', 'approved', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    budget DECIMAL(10,2),
    start_date DATE,
    end_date DATE,
    estimated_completion DATE,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Project Status History (Track status changes with admin responsible)
CREATE TABLE IF NOT EXISTS project_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    admin_id INT,
    notes TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
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

-- Portfolio/Showcase Projects
CREATE TABLE IF NOT EXISTS portfolio_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    technologies VARCHAR(255),
    image_url VARCHAR(255),
    demo_url VARCHAR(255),
    github_url VARCHAR(255),
    category VARCHAR(50),
    featured BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Company Information (About section content)
CREATE TABLE IF NOT EXISTS company_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(50) NOT NULL,
    title VARCHAR(200),
    content TEXT,
    image_url VARCHAR(255),
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services offered
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    price_range VARCHAR(50),
    features JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

-- Insert default admin user
INSERT IGNORE INTO users (username, email, password, role, first_name, last_name, status) 
VALUES ('admin', 'admin@zeroonelabs.com', '$2y$10$placeholder', 'admin', 'Admin', 'User', 'active');

-- Insert sample portfolio projects
INSERT IGNORE INTO portfolio_projects (title, description, technologies, category, featured, display_order) VALUES
('E-commerce Platform', 'Modern e-commerce solution with React and Node.js', 'React, Node.js, MongoDB, Stripe', 'web-app', TRUE, 1),
('Corporate Website', 'Professional corporate website with CMS', 'WordPress, PHP, MySQL', 'website', TRUE, 2),
('Mobile App Backend', 'RESTful API for mobile applications', 'Node.js, Express, PostgreSQL', 'api', FALSE, 3);

-- Insert company information
INSERT IGNORE INTO company_info (section, title, content, display_order) VALUES
('about', 'Our Story', 'Zero One Labs is a leading web development company specializing in innovative digital solutions.', 1),
('mission', 'Our Mission', 'To deliver cutting-edge web solutions that drive business growth and digital transformation.', 2),
('vision', 'Our Vision', 'To be the premier choice for businesses seeking exceptional web development services.', 3);

-- Insert services
INSERT IGNORE INTO services (service_name, description, icon, price_range, features, display_order) VALUES
('Web Development', 'Custom web applications and websites', 'fas fa-code', '$2,000 - $10,000', '["Responsive Design", "SEO Optimization", "Performance Optimization"]', 1),
('E-commerce Solutions', 'Online stores and payment integration', 'fas fa-shopping-cart', '$3,000 - $15,000', '["Payment Gateway", "Inventory Management", "Order Tracking"]', 2),
('Mobile App Development', 'iOS and Android applications', 'fas fa-mobile-alt', '$5,000 - $25,000', '["Cross-platform", "Native Performance", "App Store Deployment"]', 3);

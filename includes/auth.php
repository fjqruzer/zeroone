<?php
session_start();
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($username, $password) {
    try {
        $query = "SELECT id, username, email, password, role, first_name, last_name, status FROM users WHERE (username = :username OR email = :username) AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug: Check if password verification works
            if (password_verify($password, $user['password'])) {
                // Create session
                $session_token = bin2hex(random_bytes(32));
                $this->createSession($user['id'], $session_token);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['session_token'] = $session_token;
                $_SESSION['last_activity'] = time();
                
                return ['success' => true, 'role' => $user['role']];
            } else {
                // For debugging - remove in production
                error_log("Password verification failed for user: " . $username);
                error_log("Provided password: " . $password);
                error_log("Stored hash: " . $user['password']);
            }
        }
        return ['success' => false, 'message' => 'Invalid credentials'];
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed'];
    }
}

// Add a method to properly hash passwords
public function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Update the register method to support phone parameter
public function register($username, $email, $password, $first_name, $last_name, $role = 'client', $company = null, $phone = null) {
    try {
        // Check if user already exists
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash the password properly
        $hashed_password = $this->hashPassword($password);
        
        // Set status based on role
        $status = ($role === 'admin') ? 'active' : 'pending';
        
        // Insert new user
        $query = "INSERT INTO users (username, email, password, role, first_name, last_name, company, phone, status) VALUES (:username, :email, :password, :role, :first_name, :last_name, :company, :phone, :status)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':company', $company);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User registered successfully'];
        } else {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed'];
    }
}
    
    private function createSession($user_id, $session_token) {
        $query = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (:user_id, :session_token, :ip_address, :user_agent, DATE_ADD(NOW(), INTERVAL 24 HOUR))";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':session_token', $session_token);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        $stmt->execute();
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
            return false;
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        if ($_SESSION['role'] !== $role) {
            header('Location: unauthorized.php');
            exit();
        }
    }
    
    public function logout() {
        if (isset($_SESSION['session_token'])) {
            $query = "DELETE FROM user_sessions WHERE session_token = :session_token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':session_token', $_SESSION['session_token']);
            $stmt->execute();
        }
        
        session_destroy();
        header('Location: login.php');
        exit();
    }
}
?>

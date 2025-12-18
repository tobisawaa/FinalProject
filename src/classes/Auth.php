<?php
require_once __DIR__ . '/Database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function register($name, $email, $password, $role = 'user') {
        // Check if email exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$email]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $userId = $this->db->insert('users', [
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role
            ]);
            
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function login($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email atau password salah'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Email atau password salah'];
        }
        
        // Check if email is verified
        if (!$user['is_verified']) {
            return ['success' => false, 'message' => 'Email Anda belum diverifikasi. Silahkan cek email Anda untuk kode verifikasi.'];
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        // store profile picture path in session for quick access
        $_SESSION['user_profile_picture'] = isset($user['profile_picture']) ? $user['profile_picture'] : null;
        
        return ['success' => true, 'user' => $user];
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        // fetch fresh user info from DB to include profile_picture and any updates
        $user = $this->db->fetchOne("SELECT id, name, email, role, profile_picture FROM users WHERE id = ?", [$_SESSION['user_id']]);
        if ($user) {
            // keep session in sync
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_profile_picture'] = $user['profile_picture'] ?? null;
            return $user;
        }
        // fallback
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'profile_picture' => $_SESSION['user_profile_picture'] ?? null
        ];
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
    
    public function isGuest() {
        return isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true;
    }
    
    public function guestLogin($guestName = 'Guest') {
        $_SESSION['guest_mode'] = true;
        $_SESSION['guest_name'] = $guestName;
        $_SESSION['guest_session_id'] = session_id();
        $_SESSION['guest_ip'] = $this->getClientIp();
        $_SESSION['guest_login_time'] = time();
        
        try {
            // Log guest session to database
            $this->db->insert('guest_sessions', [
                'session_id' => session_id(),
                'guest_name' => $guestName,
                'ip_address' => $this->getClientIp()
            ]);
        } catch (Exception $e) {
            // Continue even if logging fails
        }
        
        return ['success' => true, 'message' => 'Guest mode activated'];
    }
    
    public function logoutGuest() {
        unset($_SESSION['guest_mode']);
        unset($_SESSION['guest_name']);
        unset($_SESSION['guest_session_id']);
        unset($_SESSION['guest_ip']);
        unset($_SESSION['guest_login_time']);
        return true;
    }
    
    public function adminLogin($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND role = 'admin'",
            [$email]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid admin credentials'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid admin credentials'];
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = 'admin';
        $_SESSION['admin_login_time'] = time();
        
        // Log admin login
        try {
            $this->db->insert('admin_logs', [
                'admin_id' => $user['id'],
                'action' => 'LOGIN',
                'description' => 'Admin login successful',
                'ip_address' => $this->getClientIp()
            ]);
        } catch (Exception $e) {
            // Continue even if logging fails
        }
        
        return ['success' => true, 'user' => $user];
    }
    
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    public function canCreateActivity() {
        // Only regular users and admins can create activities
        // Guests cannot create activities
        return $this->isLoggedIn() && !$this->isGuest();
    }
    
    public function canEditActivity($activityOwnerId) {
        if (!$this->isLoggedIn() || $this->isGuest()) {
            return false;
        }
        
        $user = $this->getCurrentUser();
        // Admin can edit any activity, users can only edit their own
        return $user['role'] === 'admin' || $user['id'] === $activityOwnerId;
    }
    
    public function canDeleteActivity($activityOwnerId) {
        if (!$this->isLoggedIn() || $this->isGuest()) {
            return false;
        }
        
        $user = $this->getCurrentUser();
        // Admin can delete any activity, users can only delete their own
        return $user['role'] === 'admin' || $user['id'] === $activityOwnerId;
    }
    
    public function getDb() {
        return $this->db;
    }
}
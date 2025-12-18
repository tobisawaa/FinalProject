<?php

namespace Middleware;

use Classes\Auth;

/**
 * AuthMiddleware
 * Middleware untuk mengecek autentikasi user sebelum akses ke halaman tertentu
 */
class AuthMiddleware {
    
    /**
     * Check apakah user sudah login
     * Jika tidak, redirect ke login
     */
    public static function requireLogin() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header("Location: /login.php");
            exit();
        }
    }

    /**
     * Check apakah user sudah login dan email sudah terverifikasi
     * Jika tidak, redirect ke halaman verifikasi email
     */
    public static function requireVerifiedEmail() {
        self::requireLogin();
        
        if (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] != 1) {
            header("Location: /public/verify_email.php");
            exit();
        }
    }

    /**
     * Check apakah user adalah admin
     * Jika tidak, redirect ke halaman tidak diizinkan
     */
    public static function requireAdmin() {
        self::requireLogin();
        
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
            http_response_code(403);
            die("Access denied. Admin role required.");
        }
    }

    /**
     * Check apakah user sudah logout
     * Jika sudah login, redirect ke dashboard
     */
    public static function requireLogout() {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            header("Location: /public/dashboard.php");
            exit();
        }
    }

    /**
     * Check CSRF token
     */
    public static function validateCSRFToken() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
                return false;
            }
            
            if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Check rate limiting untuk login attempt
     */
    public static function checkLoginRateLimit($identifier, $maxAttempts = 5, $windowSeconds = 900) {
        $key = "login_attempt_" . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }

        $window = time() - $_SESSION[$key]['first_attempt'];
        
        // Reset jika sudah lewat window
        if ($window > $windowSeconds) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
            return true;
        }

        // Check apakah sudah exceed max attempts
        if ($_SESSION[$key]['attempts'] >= $maxAttempts) {
            return false;
        }

        return true;
    }

    /**
     * Increment login attempt counter
     */
    public static function incrementLoginAttempt($identifier) {
        $key = "login_attempt_" . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }

        $_SESSION[$key]['attempts']++;
    }

    /**
     * Reset login attempt counter
     */
    public static function resetLoginAttempt($identifier) {
        $key = "login_attempt_" . md5($identifier);
        unset($_SESSION[$key]);
    }

    /**
     * Check apakah request method valid untuk action tertentu
     */
    public static function validateRequestMethod($allowedMethods = ['GET']) {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        
        if (!in_array($method, $allowedMethods)) {
            http_response_code(405);
            die("Method Not Allowed");
        }
    }

    /**
     * Check apakah user punya permission tertentu
     */
    public static function hasPermission($permission) {
        if (!isset($_SESSION['permissions'])) {
            return false;
        }

        return in_array($permission, $_SESSION['permissions']);
    }
}

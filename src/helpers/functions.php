<?php

/**
 * Helper Functions
 * Utility functions yang dapat digunakan di seluruh aplikasi
 */

/**
 * Redirect ke URL tertentu
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Dapatkan user ID dari session
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Dapatkan user dari session
 */
function getUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Ambil dan hapus flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validasi email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validasi password strength
 */
function isValidPassword($password) {
    // Minimal 8 karakter, harus ada huruf besar, huruf kecil, angka
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verifikasi password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Format tanggal
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '-';
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return '-';
    }
}

/**
 * Cek apakah request adalah AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Return JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Cek apakah email sudah terverifikasi
 */
function isEmailVerified($userId) {
    try {
        $db = new Database();
        $result = $db->query("SELECT email_verified FROM users WHERE id = ?", [$userId]);
        return $result && $result['email_verified'] == 1;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get base URL
 */
function baseUrl($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    return $protocol . '://' . $host . $basePath . ($path ? '/' . $path : '');
}

/**
 * Log error ke file
 */
function logError($message, $context = []) {
    $logFile = __DIR__ . '/../../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
    $logMessage = "[$timestamp] $message$contextStr\n";
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

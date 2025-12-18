<?php
// Database migration: Add email verification and password reset support
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$migrations = [];
$errors = [];

// 1. Add is_verified column to users table
try {
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    if ($checkColumn->rowCount() == 0) {
        $conn->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER password");
        $migrations[] = "✓ Kolom 'is_verified' ditambahkan ke tabel users";
    } else {
        $migrations[] = "✓ Kolom 'is_verified' sudah ada di tabel users";
    }
} catch (PDOException $e) {
    $errors[] = "Error checking is_verified column: " . $e->getMessage();
}

// 2. Create email_verifications table
try {
    $sql = "CREATE TABLE IF NOT EXISTS email_verifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(255) NOT NULL,
        type VARCHAR(50) DEFAULT 'register',
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_type (type),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $conn->exec($sql);
    $migrations[] = "✓ Tabel email_verifications dibuat";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        $migrations[] = "✓ Tabel email_verifications sudah ada";
    } else {
        $errors[] = "Error creating email_verifications table: " . $e->getMessage();
    }
}

// 3. Create password_resets table
try {
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NULL,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_email (email),
        INDEX idx_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $conn->exec($sql);
    $migrations[] = "✓ Tabel password_resets dibuat";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        $migrations[] = "✓ Tabel password_resets sudah ada";
    } else {
        $errors[] = "Error creating password_resets table: " . $e->getMessage();
    }
}

echo "=== Database Migration: Email Verification & Password Reset ===\n\n";

foreach ($migrations as $msg) {
    echo $msg . "\n";
}

if ($errors) {
    echo "\n⚠️  Warnings/Errors:\n";
    foreach ($errors as $err) {
        echo "  " . $err . "\n";
    }
} else {
    echo "\n✅ Semua migrasi berhasil!\n";
}

echo "\n📝 Catatan:\n";
echo "   - Users harus verify email sebelum bisa login\n";
echo "   - OTP berlaku 15 menit\n";
echo "   - Password reset juga menggunakan OTP\n";
?>

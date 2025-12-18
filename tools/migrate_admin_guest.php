<?php
// Database migration: Add admin and guest support
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$migrations = [];
$created = [];
$errors = [];

// 1. Check if users table exists and add role column if needed
try {
    $checkRole = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($checkRole->rowCount() == 0) {
        $conn->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user' AFTER password");
        $migrations[] = "✓ Kolom 'role' ditambahkan ke tabel users";
    } else {
        $migrations[] = "✓ Kolom 'role' sudah ada di tabel users";
    }
} catch (PDOException $e) {
    $errors[] = "Error checking role column: " . $e->getMessage();
}

// 2. Create admin_logs table untuk tracking admin actions
try {
    $sql = "CREATE TABLE IF NOT EXISTS admin_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        target_user_id INT,
        target_table VARCHAR(100),
        target_id INT,
        description TEXT,
        old_values JSON,
        new_values JSON,
        ip_address VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_admin (admin_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $conn->exec($sql);
    $migrations[] = "✓ Tabel admin_logs dibuat";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        $migrations[] = "✓ Tabel admin_logs sudah ada";
    } else {
        $errors[] = "Error creating admin_logs table: " . $e->getMessage();
    }
}

// 3. Create guest_sessions table untuk tracking guest access
try {
    $sql = "CREATE TABLE IF NOT EXISTS guest_sessions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        session_id VARCHAR(255) NOT NULL UNIQUE,
        guest_name VARCHAR(255),
        ip_address VARCHAR(50),
        access_count INT DEFAULT 1,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        INDEX idx_session_id (session_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $conn->exec($sql);
    $migrations[] = "✓ Tabel guest_sessions dibuat";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        $migrations[] = "✓ Tabel guest_sessions sudah ada";
    } else {
        $errors[] = "Error creating guest_sessions table: " . $e->getMessage();
    }
}

// 4. Create an admin user if none exists
try {
    $checkAdmin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if ($checkAdmin->rowCount() == 0) {
        // Create default admin
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Administrator', 'admin@app.com', $adminPassword, 'admin']);
        $migrations[] = "✓ Akun admin default dibuat (email: admin@app.com, password: admin123)";
    } else {
        $migrations[] = "✓ Akun admin sudah ada";
    }
} catch (PDOException $e) {
    $errors[] = "Error creating admin user: " . $e->getMessage();
}

echo "=== Database Migration: Admin & Guest Support ===\n\n";

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

echo "\n📝 Admin Default User:\n";
echo "   Email: admin@app.com\n";
echo "   Password: admin123\n";
echo "   ⚠️  SEGERA UBAH PASSWORD SETELAH LOGIN PERTAMA KALI!\n";
?>

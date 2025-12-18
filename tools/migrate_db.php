<?php
// Database migration: Create tables for push notifications
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/classes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$tables = [
    // Table untuk menyimpan push subscriptions dari user
    "CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        endpoint VARCHAR(500) NOT NULL,
        public_key TEXT NOT NULL,
        auth_token TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_subscription (user_id, endpoint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // Table untuk log semua notifikasi yang dikirim
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        type VARCHAR(50) DEFAULT 'general',
        status VARCHAR(50) DEFAULT 'pending',
        sent_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_status (status),
        INDEX idx_type (type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

$created = [];
$errors = [];

foreach ($tables as $sql) {
    try {
        $conn->exec($sql);
        $created[] = "✓ Table created successfully";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $created[] = "✓ Table already exists";
        } else {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

echo "=== Database Migration ===\n\n";
echo "Creating push_subscriptions table...\n";
echo array_shift($created) . "\n\n";

echo "Creating notifications table...\n";
echo array_shift($created) . "\n\n";

if ($errors) {
    echo "Errors:\n";
    foreach ($errors as $err) {
        echo "  " . $err . "\n";
    }
} else {
    echo "✅ All tables created successfully!\n";
}
?>

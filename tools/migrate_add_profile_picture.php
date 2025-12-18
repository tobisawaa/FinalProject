<?php
require_once __DIR__ . '/../src/classes/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL DEFAULT NULL AFTER email");
    echo "✓ 'profile_picture' column added or already exists.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ 'profile_picture' column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "Migration done.\n";

?>

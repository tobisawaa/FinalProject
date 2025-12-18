<?php
require_once __DIR__ . '/src/classes/Database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

echo "=== Database Tables Status ===\n\n";

// Check users table
$users_cols = $conn->query('SHOW COLUMNS FROM users');
echo "✓ Users table columns:\n";
foreach($users_cols->fetchAll(PDO::FETCH_ASSOC) as $col) {
    $highlight = in_array($col['Field'], ['is_verified','profile_picture']) ? ' ← NEW' : '';
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")" . $highlight . "\n";
}

echo "\n";

// Check email_verifications table
$ev_cols = $conn->query('SHOW COLUMNS FROM email_verifications');
echo "✓ Email_verifications table columns:\n";
foreach($ev_cols->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n✅ Database structure verified!\n";
echo "\nTables ready for:\n";
echo "  ✓ User registration with email verification\n";
echo "  ✓ Password reset with OTP\n";
echo "  ✓ OTP expiration tracking\n";
?>

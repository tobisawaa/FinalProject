<?php
// Run weather alert checks for all users. Intended to be run from CLI (cron).
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/NotificationService.php';

$db = Database::getInstance();
$notif = new NotificationService();

// Fetch users
$users = $db->fetchAll("SELECT id, name, email, city FROM users");
if (!$users) {
    echo "No users found\n";
    exit;
}

foreach ($users as $u) {
    $userId = $u['id'];
    $city = isset($u['city']) && $u['city'] ? $u['city'] : 'Jakarta';
    echo "Checking alerts for user {$userId} ({$u['email']}) in city {$city}...\n";
    try {
        $has = $notif->checkWeatherAlerts($userId, $city);
        echo $has ? "Alerts sent\n" : "No alerts\n";
    } catch (Exception $e) {
        echo "Error for user {$userId}: " . $e->getMessage() . "\n";
    }
}

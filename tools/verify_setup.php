<?php
// Verification script - check if push notification system is properly configured

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🔔 PUSH NOTIFICATION SYSTEM - VERIFICATION                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$checks = [];
$allPassed = true;

// 1. Check Composer dependencies
echo "Checking Composer dependencies...\n";
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorPath)) {
    require_once $vendorPath;
    if (class_exists('\\Minishlink\\WebPush\\WebPush')) {
        $checks['Composer Dependencies'] = ['✅ OK', true];
    } else {
        $checks['Composer Dependencies'] = ['❌ FAILED - WebPush class not found', false];
        $allPassed = false;
    }
} else {
    $checks['Composer Dependencies'] = ['❌ FAILED - vendor/autoload.php not found', false];
    $allPassed = false;
}

// 2. Check VAPID keys
echo "Checking VAPID configuration...\n";
require_once __DIR__ . '/../config/env.php';

$publicKey = env('VAPID_PUBLIC_KEY');
$privateKey = env('VAPID_PRIVATE_KEY');
$subject = env('VAPID_SUBJECT');

if ($publicKey && $privateKey && $subject) {
    $checks['VAPID Keys'] = ['✅ OK - Keys configured', true];
} else {
    $checks['VAPID Keys'] = ['❌ FAILED - Missing VAPID keys in .env', false];
    $allPassed = false;
}

// 3. Check database tables
echo "Checking database tables...\n";
require_once __DIR__ . '/../src/classes/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $pushSubsExists = $conn->query("SHOW TABLES LIKE 'push_subscriptions'")->fetch();
    $notifExists = $conn->query("SHOW TABLES LIKE 'notifications'")->fetch();
    
    if ($pushSubsExists && $notifExists) {
        // Count existing subscriptions
        $subCount = $conn->query("SELECT COUNT(*) as cnt FROM push_subscriptions")->fetch();
        $notifCount = $conn->query("SELECT COUNT(*) as cnt FROM notifications")->fetch();
        $checks['Database Tables'] = [
            "✅ OK - push_subscriptions ({$subCount['cnt']} records), notifications ({$notifCount['cnt']} records)",
            true
        ];
    } else {
        $checks['Database Tables'] = ['❌ FAILED - Tables not found. Run: php tools/migrate_db.php', false];
        $allPassed = false;
    }
} catch (Exception $e) {
    $checks['Database Tables'] = ['❌ FAILED - ' . $e->getMessage(), false];
    $allPassed = false;
}

// 4. Check Service Worker
echo "Checking Service Worker...\n";
$swPath = __DIR__ . '/../public/sw.js';
if (file_exists($swPath)) {
    $checks['Service Worker'] = ['✅ OK - /public/sw.js exists', true];
} else {
    $checks['Service Worker'] = ['❌ FAILED - /public/sw.js not found', false];
    $allPassed = false;
}

// 5. Check Client subscription script
echo "Checking client subscription script...\n";
$appJsPath = __DIR__ . '/../public/assets/js/app.js';
if (file_exists($appJsPath) && filesize($appJsPath) > 100) {
    $checks['Client Subscription'] = ['✅ OK - /public/assets/js/app.js ready', true];
} else {
    $checks['Client Subscription'] = ['❌ FAILED - app.js not found or empty', false];
    $allPassed = false;
}

// 6. Check VAPID endpoint
echo "Checking VAPID endpoint...\n";
$vapidPath = __DIR__ . '/../public/api/vapid.php';
if (file_exists($vapidPath)) {
    $checks['VAPID Endpoint'] = ['✅ OK - /api/vapid.php exists', true];
} else {
    $checks['VAPID Endpoint'] = ['❌ FAILED - /api/vapid.php not found', false];
    $allPassed = false;
}

// 7. Check Alert Runner script
echo "Checking alert runner script...\n";
$runAlertsPath = __DIR__ . '/../tools/run_alerts.php';
if (file_exists($runAlertsPath)) {
    $checks['Alert Runner'] = ['✅ OK - tools/run_alerts.php ready', true];
} else {
    $checks['Alert Runner'] = ['❌ FAILED - run_alerts.php not found', false];
    $allPassed = false;
}

// 8. Check OpenWeatherMap API 
echo "Checking OpenWeatherMap API...\n";
$apiKey = env('OPENWEATHER_API_KEY');
if ($apiKey && strlen($apiKey) > 10) {
    $checks['Weather API'] = ['✅ OK - API key configured', true];
} else {
    $checks['Weather API'] = ['❌ FAILED - API key not configured', false];
    $allPassed = false;
}

// Display results
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICATION RESULTS                                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

foreach ($checks as $name => $result) {
    echo sprintf("%-30s %s\n", $name . ":", $result[0]);
}

echo "\n";
if ($allPassed) {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ ALL CHECKS PASSED - SYSTEM READY!                     ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    echo "Next steps:\n";
    echo "1. Login to the application in your browser\n";
    echo "2. Allow notification permission when prompted\n";
    echo "3. Run: php tools/run_alerts.php\n";
    echo "4. Check your browser for push notifications!\n\n";
} else {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ SOME CHECKS FAILED - PLEASE FIX ABOVE ISSUES          ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
}
?>

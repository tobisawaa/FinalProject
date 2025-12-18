<?php
require_once __DIR__ . '/../src/classes/Database.php';

// Simple CLI cleanup script for weather_logs
// Usage examples:
// php tools/cleanup_weather_logs.php --days=30 --batch=1000
// php tools/cleanup_weather_logs.php --days=90 --batch=500 --dry-run

$opts = getopt('', ['days::', 'batch::', 'dry-run', 'help']);

if (isset($opts['help'])) {
    echo "Usage: php tools/cleanup_weather_logs.php [--days=30] [--batch=1000] [--dry-run]\n";
    exit;
}

$days = isset($opts['days']) ? (int)$opts['days'] : 30;
$batchSize = isset($opts['batch']) ? (int)$opts['batch'] : 1000;
$dryRun = isset($opts['dry-run']);

if ($days <= 0) $days = 30;
if ($batchSize <= 0) $batchSize = 1000;

$db = Database::getInstance();
$pdo = $db->getConnection();

// Check if index on recorded_at exists
$indexCheckSql = "SELECT COUNT(1) as cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'weather_logs' AND (column_name = 'recorded_at' OR index_name LIKE '%recorded_at%')";
$res = $pdo->query($indexCheckSql)->fetch(PDO::FETCH_ASSOC);
if ($res && intval($res['cnt']) === 0) {
    echo "Warning: No index found on 'recorded_at'.\n";
    echo "It's recommended to add an index to speed up WHERE recorded_at < ... operations:\n";
    echo "ALTER TABLE weather_logs ADD INDEX idx_recorded_at (recorded_at);\n\n";
}

$cutoff = (new DateTime())->modify("-{$days} days")->format('Y-m-d H:i:s');

echo "Cleanup weather_logs older than {$days} days (before {$cutoff}). Batch size: {$batchSize}. Dry-run: " . ($dryRun ? 'yes' : 'no') . "\n";

if ($dryRun) {
    $countSql = "SELECT COUNT(*) as cnt FROM weather_logs WHERE recorded_at < :cutoff";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute([':cutoff' => $cutoff]);
    $count = $stmt->fetchColumn();
    echo "Rows that would be deleted: {$count}\n";
    exit;
}

$totalDeleted = 0;
$start = microtime(true);

while (true) {
    // LIMIT must be injected as integer to avoid driver issues with placeholders
    $sql = "DELETE FROM weather_logs WHERE recorded_at < :cutoff LIMIT " . (int)$batchSize;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cutoff' => $cutoff]);
    $deleted = $stmt->rowCount();

    if ($deleted === 0) {
        break;
    }

    $totalDeleted += $deleted;
    echo date('Y-m-d H:i:s') . " - Deleted {$deleted} rows (total {$totalDeleted})\n";

    // Sleep briefly to reduce server load; adjust if necessary
    usleep(200000); // 0.2s
}

$elapsed = round(microtime(true) - $start, 2);
echo "Done. Total deleted: {$totalDeleted}. Time: {$elapsed}s\n";

exit;

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/classes/Auth.php';
require_once __DIR__ . '/../../src/classes/Database.php';

// 1. Cek Login
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$db = Database::getInstance(); // Ini wrapper database kamu

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validasi input
    if (!isset($input['endpoint'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Endpoint required']);
        exit;
    }

    $endpoint = $input['endpoint'];
    $p256dh = $input['keys']['p256dh'] ?? '';
    $authToken = $input['keys']['auth'] ?? '';
    
    try {
        // 2. CEK DUPLIKAT (Ini yang sebelumnya tidak ada)
        // Gunakan fetchAll atau fetchOne sesuai fitur wrapper databasemu
        // Asumsi wrapper punya method fetchAll/query seperti di NotificationService
        
        $existing = $db->fetchAll("SELECT id FROM push_subscriptions WHERE endpoint = ?", [$endpoint]);

        if ($existing && count($existing) > 0) {
            // A. Jika sudah ada: UPDATE user_id nya saja (barangkali dia ganti akun di browser yg sama)
            // Kita pakai raw query via getConnection() kalau wrapper tidak support update complex, 
            // tapi biasanya wrapper punya method update/query. Kita pakai query aman saja:
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("UPDATE push_subscriptions SET user_id = ? WHERE endpoint = ?");
            $stmt->execute([$user['id'], $endpoint]);
            
            echo json_encode(['success' => true, 'status' => 'updated', 'message' => 'Subscription updated']);
        } else {
            // B. Jika belum ada: INSERT BARU
            $data = [
                'user_id' => $user['id'],
                'endpoint' => $endpoint,
                'public_key' => $p256dh,
                'auth_token' => $authToken
            ];
            
            // Asumsi method insert kamu mengembalikan ID
            $id = $db->insert('push_subscriptions', $data);
            echo json_encode(['success' => true, 'status' => 'created', 'subscription_id' => $id]);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/classes/Auth.php';
require_once __DIR__ . '/../../src/classes/NotificationService.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$notificationService = new NotificationService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = $input['title'] ?? 'Notification';
    $message = $input['message'] ?? '';
    $type = $input['type'] ?? 'general';
    
    $result = $notificationService->sendWebPush($user['id'], $title, $message, $type);
    echo json_encode($result);
} else {
    // Get notifications
    $notifications = $notificationService->getNotifications($user['id']);
    echo json_encode(['success' => true, 'data' => $notifications]);
}
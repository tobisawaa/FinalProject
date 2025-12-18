<?php
require_once __DIR__ . '/../src/classes/Auth.php';
require_once __DIR__ . '/../src/classes/Database.php';

$auth = new Auth();
$db = Database::getInstance();

if ($auth->isGuest()) {
    // Get guest session ID before logout
    $guestSessionId = $_SESSION['guest_session_id'] ?? null;
    
    // Reset/clear guest activities if any were created during guest session
    // Note: Guest shouldn't be able to create activities, but just in case
    if ($guestSessionId) {
        try {
            // Mark guest session as inactive
            $db->update('guest_sessions', 
                ['expires_at' => date('Y-m-d H:i:s')], 
                'session_id = ?', 
                [$guestSessionId]
            );
        } catch (Exception $e) {
            // Log error but continue logout
        }
    }
    
    $auth->logoutGuest();
} else {
    $auth->logout();
}

header('Location: index.php');
exit;

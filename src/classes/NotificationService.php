<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../../config/env.php';

class NotificationService {
    private $db;
    private $webPushAvailable = false;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        $vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
            $this->webPushAvailable = class_exists('\\Minishlink\\WebPush\\WebPush');
        }
    }

    public function sendWebPush($userId, $title, $message, $type = 'general', $customImage = null) {
        $subscriptions = $this->db->fetchAll(
            "SELECT * FROM push_subscriptions WHERE user_id = ?",
            [$userId]
        );

        // --- PERBAIKAN 1: Return Array saat tidak ada subscription ---
        if (!$subscriptions || count($subscriptions) === 0) {
            $notifId = $this->logNotification($userId, $title, $message, $type, 'failed');
            // SEKARANG KITA BUNGKUS JADI ARRAY AGAR KONSISTEN
            return [
                'success' => false, 
                'notification_id' => $notifId, 
                'error' => 'No subscription found for this user'
            ];
        }

        // Logic Gambar Dinamis
        $defaultImage = 'https://images.unsplash.com/photo-1592210454132-7a6f913be204?w=600&h=300&fit=crop'; 
        
        if ($customImage) {
            $imageToUse = $customImage;
        } else {
            switch ($type) {
                case 'weather_alert': 
                    $imageToUse = 'https://images.unsplash.com/photo-1428592953211-077101b26bd1?w=600&h=300&fit=crop'; 
                    break;
                case 'activity': 
                    $imageToUse = 'https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?w=600&h=300&fit=crop';
                    break;
                default: 
                    $imageToUse = $defaultImage;
                    break;
            }
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $message,
            'icon' => '/assets/img/logo.png',
            'badge' => '/assets/img/badge.png',
            'image' => $imageToUse,
            'type' => $type,
            'url' => 'dashboard.php',
            'actions' => [
                ['action' => 'view', 'title' => '👀 Lihat Detail'],
                ['action' => 'close', 'title' => '✖ Tutup']
            ]
        ]);

        if ($this->webPushAvailable) {
            try {
                $auth = [
                    'VAPID' => [
                        'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
                        'publicKey' => env('VAPID_PUBLIC_KEY'),
                        'privateKey' => env('VAPID_PRIVATE_KEY'),
                    ],
                ];

                $webPush = new \Minishlink\WebPush\WebPush($auth);

                foreach ($subscriptions as $sub) {
                    $subscription = \Minishlink\WebPush\Subscription::create([
                        'endpoint' => $sub['endpoint'],
                        'keys' => [
                            'p256dh' => $sub['public_key'],
                            'auth' => $sub['auth_token']
                        ]
                    ]);

                    $webPush->queueNotification($subscription, $payload);
                }

                $reports = $webPush->flush();
                
                $success = true;
                foreach ($reports as $report) {
                    if (!$report->isSuccess()) {
                        $success = false;
                    }
                }

                $status = $success ? 'sent' : 'failed';
                $notifId = $this->logNotification($userId, $title, $message, $type, $status);
                
                return ['success' => $success, 'notification_id' => $notifId];

            } catch (Exception $e) {
                $this->logNotification($userId, $title, $message, $type, 'failed');
                return ['success' => false, 'error' => $e->getMessage()];
            }
        } else {
            $notifId = $this->logNotification($userId, $title, $message, $type, 'pending');
            return ['success' => true, 'notification_id' => $notifId, 'note' => 'web-push library not installed'];
        }
    }
    
    public function sendEmail($userId, $title, $message, $type = 'general') {
        $user = $this->db->fetchOne("SELECT email, name FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            $notifId = $this->logNotification($userId, $title, $message, $type, 'failed');
            return ['success' => false, 'notification_id' => $notifId, 'error' => 'User not found'];
        }
        
        $to = $user['email'];
        $subject = $title;
        $body = "Hi {$user['name']},\n\n{$message}\n\nBest regards,\nWeather Activity App";
        $headers = "From: noreply@weatherapp.local";
        
        $sent = @mail($to, $subject, $body, $headers);
        
        $status = $sent ? 'sent' : 'failed';
        $notifId = $this->logNotification($userId, $title, $message, $type, $status);
        
        return ['success' => $sent, 'notification_id' => $notifId];
    }
    
    private function logNotification($userId, $title, $message, $type, $status) {
        return $this->db->insert('notifications', [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => substr($type, 0, 50),
            'status' => $status,
            'sent_at' => ($status === 'sent') ? date('Y-m-d H:i:s') : null
        ]);
    }
    
    public function getNotifications($userId, $limit = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }
}
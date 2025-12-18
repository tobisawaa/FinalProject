<?php
require_once __DIR__ . '/../src/classes/NotificationService.php';

// Pastikan ID User benar
$userId = 7; // Ganti sesuai ID kamu

$service = new NotificationService();

echo "<h3>Mengirim Notifikasi Stylish...</h3>";

// SKENARIO 1: Peringatan Cuaca (Gambar Hujan)
$result1 = $service->sendWebPush(
    $userId, 
    "⚠️ Peringatan Cuaca Ekstrem", 
    "Hujan badai diperkirakan turun pukul 15:00. Sebaiknya hindari aktivitas luar ruangan.", 
    "weather_alert" // <--- Ini akan memicu gambar badai
);

echo "Status Alert: " . ($result1['success'] ? '✅ Terkirim' : '❌ Gagal') . "<br>";

// SKENARIO 2: Info Umum (Gambar Kota/Default)
/* Uncomment kalau mau coba yg general
$result2 = $service->sendWebPush(
    $userId, 
    "Selamat Pagi! 🌤️", 
    "Cuaca hari ini cerah berawan. Cocok untuk jogging pagi.", 
    "general"
);
*/

echo "<pre>";
print_r($result1);
echo "</pre>";
?>
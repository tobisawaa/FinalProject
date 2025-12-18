<?php
// Matikan time limit
set_time_limit(0); 

require_once __DIR__ . '/../src/classes/Database.php';
require_once __DIR__ . '/../src/classes/ApiClientWeather.php';
require_once __DIR__ . '/../src/classes/NotificationService.php';

$db = Database::getInstance()->getConnection();
$weatherClient = new ApiClientWeather();
$notifService = new NotificationService();

echo "[".date('Y-m-d H:i:s')."] Memulai pengecekan cuaca otomatis...\n";

// 1. Ambil semua User yang punya Subscription
$users = $db->query("SELECT DISTINCT user_id FROM push_subscriptions")->fetchAll(PDO::FETCH_ASSOC);

// ARRAY CACHE: Simpan data cuaca agar tidak panggil API berulang untuk kota yang sama
$weatherCache = []; 

foreach ($users as $u) {
    $userId = $u['user_id'];
    
    // TODO: Ambil kota user dari database (misal tabel users atau user_preferences)
    // Untuk sekarang kita default ke Jakarta
    $city = 'Jakarta'; 

    // --- OPTIMASI API ---
    // Cek apakah data cuaca kota ini sudah ada di cache?
    if (isset($weatherCache[$city])) {
        // Pakai data yang sudah disimpan
        $weather = $weatherCache[$city];
    } else {
        // Belum ada, baru panggil API
        $weather = $weatherClient->getCurrentWeather($city);
        // Simpan ke cache
        if ($weather) {
            $weatherCache[$city] = $weather;
        } else {
            echo "Gagal ambil cuaca kota $city untuk User ID: $userId\n";
            continue;
        }
    }
    
    // --- ANALISA CUACA ---

    $temp = round($weather['main']['temp']);
    $condition = strtolower($weather['weather'][0]['main']); 
    $desc = $weather['weather'][0]['description'];

    // Cek status hujan biar kodingan di bawah lebih rapi
    $isRaining = (strpos($condition, 'rain') !== false || strpos($condition, 'drizzle') !== false || strpos($condition, 'thunderstorm') !== false);

    // Variabel Penampung Notifikasi
    $shouldSend = false;
    $title = "";
    $message = "";
    $type = "general";

    // --- LOGIKA PRIORITAS ---
    // Kita cek Skenario C (Aktivitas Outdoor) dulu karena prioritasnya paling tinggi

    // 1. Ambil Aktivitas Outdoor Hari Ini
    $stmt = $db->prepare("SELECT title, scheduled_time FROM activities WHERE user_id = ? AND activity_type = 'outdoor' AND scheduled_date = CURDATE()");
    $stmt->execute([$userId]);
    $outdoorActs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // SKENARIO C: Hujan + Ada Jadwal Outdoor
    if ($isRaining && count($outdoorActs) > 0) {
        $shouldSend = true;
        $actTitle = $outdoorActs[0]['title'];
        $actTime = date('H:i', strtotime($outdoorActs[0]['scheduled_time']));
        
        $title = "⚠️ Warning: Jadwal Outdoor!";
        $message = "Kamu punya jadwal '$actTitle' jam $actTime, tapi di $city sedang turun $desc. Pertimbangkan untuk reschedule!";
        $type = "activity";
    }
    // SKENARIO A: Hujan Biasa (Tanpa Jadwal)
    else if ($isRaining) {
        $shouldSend = true;
        $title = "☔ Sedia Payung!";
        $message = "Di $city sedang turun $desc. Hati-hati jika beraktivitas di luar.";
        $type = "weather_alert";
    }
    // SKENARIO B: Panas Terik
    else if ($temp > 34) {
        $shouldSend = true;
        $title = "🌡️ Cuaca Panas Ekstrem";
        $message = "Suhu mencapai {$temp}°C. Jangan lupa minum air putih dan pakai sunscreen!";
        $type = "weather_alert";
    }

    // Eksekusi Pengiriman
    if ($shouldSend) {
        $result = $notifService->sendWebPush($userId, $title, $message, $type);
        
        if ($result['success']) {
            echo "-> Notifikasi TERKIRIM ke User $userId ($type): $title\n";
        } else {
            echo "-> Gagal kirim ke User $userId. Error: " . ($result['error'] ?? 'Unknown') . "\n";
        }
    } else {
        echo "-> Cuaca aman ($condition, {$temp}°C). Tidak ada notif untuk User $userId.\n";
    }
}

echo "[".date('Y-m-d H:i:s')."] Selesai.\n";
?>
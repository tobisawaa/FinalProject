<?php
require_once __DIR__ . '/../src/classes/Auth.php';
require_once __DIR__ . '/../src/classes/Activity.php';
require_once __DIR__ . '/../src/classes/ApiClientWeather.php';
require_once __DIR__ . '/../src/classes/WeatherLog.php';
require_once __DIR__ . '/../src/classes/AnalyticsService.php';
require_once __DIR__ . '/../src/classes/Database.php';

$auth = new Auth();

// Cek 1: Apakah User ID ada? (Indikasi User Asli Login)
$isUserLoggedIn = $auth->isLoggedIn(); 

if ($isUserLoggedIn) {
    $isGuest = false;
    
    // Bersihkan sisa-sisa guest jika masih nyangkut
    if (isset($_SESSION['guest_name'])) {
        unset($_SESSION['guest_name']);
    }
} else {
    // Baru kita cek apakah dia Guest?
    $isGuest = $auth->isGuest();
}

// Redirect jika tidak punya akses sama sekali
if (!$isUserLoggedIn && !$isGuest) {
    header('Location: login.php');
    exit;
}

// Handle user data
if ($isGuest) {
    $user = [
        'id' => 0,
        'name' => $_SESSION['guest_name'] ?? 'Guest',
        'email' => 'guest@app.local',
        'role' => 'guest'
    ];
} else {
    $user = $auth->getCurrentUser();
}

// Layout selalu full width
$mainColClass = 'col-12';

$activity = new Activity();
$weatherClient = new ApiClientWeather();
$weatherLog = new WeatherLog();
$analytics = new AnalyticsService();
$db = Database::getInstance()->getConnection();

// --- LOGIC TAMBAHAN: Cek Status Subscription ---
$isSubscribed = false;
if (!$isGuest) {
    // Cek apakah user ID ini sudah ada di tabel push_subscriptions
    $stmtSub = $db->prepare("SELECT id FROM push_subscriptions WHERE user_id = ? LIMIT 1");
    $stmtSub->execute([$user['id']]);
    if ($stmtSub->rowCount() > 0) {
        $isSubscribed = true;
    }
}
// -----------------------------------------------

// Default city
$city = $_GET['city'] ?? $_SESSION['selected_city'] ?? 'Jakarta';
$_SESSION['selected_city'] = $city;

// Ambil Daftar Kota dari DB
$savedCities = $db->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Data Fetching
$currentWeather = $weatherClient->getCurrentWeather($city);
$upcomingActivities = $activity->getUpcoming($user['id'], 7);
$activityStats = $analytics->getUserActivityStats($user['id']);
$weatherSummary = $analytics->getWeatherSummary($city, 7);
$recommendations = $analytics->getActivityRecommendations($city);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Weather App</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile-improvements.css">
    <link rel="stylesheet" href="assets/css/city-wheel.css">
    <script src="assets/js/city-wheel.js?v=3"></script>
    <style>
        /* CSS Khusus untuk tombol style Cyan Mobile */
        .btn-cyan-mobile {
            background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
            border: none;
            color: white;
            font-weight: 600;
        }
        .btn-cyan-mobile:hover {
            color: white;
            opacity: 0.9;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid px-4 mt-4">
        <div class="row">
            
            <div class="<?= $mainColClass ?> mb-4">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 animate-up gap-3">
                    <div>
                        <h2 class="fw-bold text-dark mb-0">Overview</h2>
                        <p class="text-muted mb-0">Halo, <?= htmlspecialchars($user['name']) ?>!</p>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2" style="position: relative; z-index: 100;">
                        
                        <?php if (!$isGuest): ?>
                            <?php if ($isSubscribed): ?>
                                <button id="notifBtn" onclick="subscribeNotifications()" class="btn btn-success text-white border rounded-pill px-3 py-2 shadow-sm d-flex align-items-center gap-2" style="transition: all 0.2s;">
                                    <i class="bi bi-bell-fill"></i>
                                    <span class="d-none d-sm-inline fw-bold" style="font-size: 0.9rem;">Aktif</span>
                                </button>
                            <?php else: ?>
                                <button id="notifBtn" onclick="subscribeNotifications()" class="btn btn-white bg-white text-primary border rounded-pill px-3 py-2 shadow-sm d-flex align-items-center gap-2" style="transition: all 0.2s;">
                                    <i class="bi bi-bell"></i>
                                    <span class="d-none d-sm-inline fw-bold" style="font-size: 0.9rem;">Notif</span>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>

                        <button id="dashboardWheelBtn" type="button" class="btn btn-dark rounded-pill px-4 py-2 d-flex align-items-center gap-2 shadow-sm border-0" 
                                style="background: linear-gradient(135deg, #1e1e1e, #3a3a3a); transition: transform 0.2s; cursor: pointer;">
                            <i class="bi bi-crosshair text-warning"></i>
                            <span class="fw-bold text-white"><?= htmlspecialchars($city) ?></span>
                            <small class="text-white-50 ms-2" style="font-size: 0.7em;">(UBAH)</small>
                        </button>
                    </div>
                </div>

                <div class="card weather-card-bg mb-4 animate-up" style="animation-delay: 0.1s;">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            
                            <div class="col-md-6 text-center text-md-start">
                                <h5 class="opacity-75 mb-1">
                                    <i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($city) ?>
                                </h5>
                                
                                <div class="d-flex flex-column flex-md-row align-items-center gap-3 mt-2 mt-md-0">
                                    <?php if ($currentWeather): ?>
                                        <h1 class="display-1 fw-bold mb-0 text-shadow">
                                            <?= round($currentWeather['main']['temp']) ?>°
                                        </h1>
                                        
                                        <div class="text-center text-md-start">
                                            <h4 class="mb-0 text-capitalize fw-bold"><?= $currentWeather['weather'][0]['description'] ?></h4>
                                            <small class="opacity-75">Terasa: <?= round($currentWeather['main']['feels_like']) ?>°C</small>
                                        </div>
                                    <?php else: ?>
                                        <h3>Data tidak tersedia</h3>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-6 mt-4 mt-md-0">
                                <div class="row g-2 text-center justify-content-center">
                                    <div class="col-4">
                                        <div class="p-2 rounded bg-white bg-opacity-10 h-100 d-flex flex-column justify-content-center">
                                            <i class="bi bi-droplet-fill h4 d-block mb-1"></i>
                                            <small class="d-block mb-1">Kelembapan</small>
                                            <div class="fw-bold"><?= $currentWeather ? $currentWeather['main']['humidity'] : '-' ?>%</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded bg-white bg-opacity-10 h-100 d-flex flex-column justify-content-center">
                                            <i class="bi bi-wind h4 d-block mb-1"></i>
                                            <small class="d-block mb-1">Angin</small>
                                            <div class="fw-bold"><?= $currentWeather ? $currentWeather['wind']['speed'] : '-' ?> m/s</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded bg-white bg-opacity-10 h-100 d-flex flex-column justify-content-center">
                                            <i class="bi bi-cloud-fill h4 d-block mb-1"></i>
                                            <small class="d-block mb-1">Awan</small>
                                            <div class="fw-bold"><?= $currentWeather ? $currentWeather['clouds']['all'] : '-' ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div> <div class="row mt-4">
                            <div class="col-12 text-center text-md-start">
                                <a href="weather.php?city=<?= urlencode($city) ?>" 
                                   class="btn btn-sm btn-light bg-white bg-opacity-25 border-0 rounded-pill text-white px-4 py-2 shadow-sm backdrop-blur w-100 w-md-auto">
                                    <i class="bi bi-eye-fill me-2"></i>Lihat Detail Lengkap
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 animate-up" style="animation-delay: 0.2s;">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title text-success"><i class="bi bi-tree"></i> Rekomendasi Outdoor</h5>
                                    <?php if (isset($recommendations['outdoor'])): ?>
                                        <span class="badge bg-<?= $recommendations['outdoor']['suitable'] ? 'success' : 'danger' ?>">
                                            Score: <?= $recommendations['outdoor']['score'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($recommendations['outdoor'])): ?>
                                    <p class="text-muted"><?= $recommendations['outdoor']['message'] ?></p>
                                    <?php if (!empty($recommendations['outdoor']['suggestions'])): ?>
                                        <div class="mt-3">
                                            <h6 class="fw-bold small text-uppercase text-muted">Aktivitas Terbaik:</h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($recommendations['outdoor']['suggestions'] as $suggestion): ?>
                                                    <span class="badge bg-light text-dark border"><?= $suggestion ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 animate-up" style="animation-delay: 0.3s;">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title text-primary"><i class="bi bi-house-heart"></i> Rekomendasi Indoor</h5>
                                    <?php if (isset($recommendations['indoor'])): ?>
                                        <span class="badge bg-info">Score: <?= $recommendations['indoor']['score'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($recommendations['indoor'])): ?>
                                    <p class="text-muted"><?= $recommendations['indoor']['message'] ?></p>
                                    <?php if (!empty($recommendations['indoor']['suggestions'])): ?>
                                        <div class="mt-3">
                                            <h6 class="fw-bold small text-uppercase text-muted">Ide Nyaman:</h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($recommendations['indoor']['suggestions'] as $suggestion): ?>
                                                    <span class="badge bg-light text-dark border"><?= $suggestion ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row animate-up" style="animation-delay: 0.4s;">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Forecast Suhu (7 Hari)</h5>
                                <div style="position: relative; height: 300px; width: 100%;">
                                    <canvas id="temperatureChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Distribusi Cuaca (Bulanan)</h5>
                                <div style="position: relative; height: 300px; width: 100%;">
                                    <canvas id="conditionsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card animate-up" style="animation-delay: 0.5s;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0 fw-bold text-dark">Agenda Mendatang</h5>
                            
                            <div class="<?= empty($upcomingActivities) ? 'd-none d-md-block' : '' ?>">
                                <?php if ($isGuest): ?>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#guestRestrictModal">
                                        <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Buat Baru</span>
                                    </button>
                                <?php else: ?>
                                    <a href="activities/create.php" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                        <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Buat Baru</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (empty($upcomingActivities)): ?>
                            
                            <div class="d-block d-md-none text-center py-4">
                                <div class="mb-3 position-relative d-inline-block">
                                    <div style="width: 70px; height: 70px; background: #eef5ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                        <i class="bi bi-calendar-check text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                    <i class="bi bi-stars text-warning position-absolute top-0 end-0 fs-5" style="transform: translate(20%, -20%);"></i>
                                </div>
                                
                                <h6 class="fw-bold text-dark mt-2 mb-1">Hari ini masih kosong, nih!</h6>
                                <p class="text-muted small mx-auto mb-4" style="max-width: 280px; font-size: 0.85rem;">
                                    <?php if ($isGuest): ?>
                                        Login untuk mulai mencatat jadwal dan aktivitas serumu.
                                    <?php else: ?>
                                        Ayo mulai mencatat jadwal dan aktivitas serumu.
                                    <?php endif; ?>
                                </p>

                                <?php if ($isGuest): ?>
                                    <button type="button" class="btn btn-cyan-mobile rounded-pill px-4 py-2 w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#guestRestrictModal">
                                        <i class="bi bi-plus-circle me-2"></i>Buat Jadwal Baru
                                    </button>
                                <?php else: ?>
                                    <a href="activities/create.php" class="btn btn-cyan-mobile rounded-pill px-4 py-2 w-100 shadow-sm">
                                        <i class="bi bi-plus-circle me-2"></i>Buat Jadwal Baru
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="d-none d-md-block text-center py-5 text-muted">
                                <i class="bi bi-calendar-x display-6 d-block mb-2 opacity-50"></i>
                                <?php if ($isGuest): ?>
                                    Belum ada aktivitas. Login untuk mulai merencanakan harimu!
                                <?php else: ?>
                                    Belum ada rencana aktivitas. Yuk buat sekarang!
                                <?php endif; ?>
                                </div>

                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <thead class="bg-light text-muted small text-uppercase">
                                        <tr>
                                            <th class="ps-3 rounded-start">Aktivitas</th>
                                            <th>Kategori</th>
                                            <th>Lokasi</th>
                                            <th class="rounded-end">Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingActivities as $act): ?>
                                            <tr class="border-bottom">
                                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($act['title']) ?></td>
                                                <td>
                                                    <span class="badge rounded-pill bg-<?= $act['activity_type'] === 'outdoor' ? 'success' : 'info' ?> bg-opacity-10 text-<?= $act['activity_type'] === 'outdoor' ? 'success' : 'info' ?> px-3 py-2">
                                                        <?= ucfirst($act['activity_type']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted"><small><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($act['location']) ?></small></td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold text-dark small"><?= date('d M', strtotime($act['scheduled_date'])) ?></span>
                                                        <span class="text-muted small"><?= date('H:i', strtotime($act['scheduled_time'])) ?></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div> 
            
        </div>
    </div>

    <?php if ($isGuest): ?>
    <div class="modal fade" id="guestRestrictModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
                <div class="modal-header border-0 p-0 justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 80px; height: 80px;">
                            <i class="bi bi-stars fs-1"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark">Fitur Eksklusif!</h3>
                    <p class="text-muted mx-auto mb-4" style="max-width: 300px;">
                        Maaf, Fitur pembuatan jadwal hanya tersedia untuk pengguna terdaftar. Yuk, gabung sekarang!
                    </p>
                    
                    <div class="d-grid gap-2 col-10 mx-auto">
                        <a href="register.php" class="btn btn-primary btn-lg rounded-pill fw-bold shadow">
                            Daftar Akun Gratis
                        </a>
                        <a href="login.php" class="btn btn-light btn-lg rounded-pill text-muted">
                            Sudah punya akun? Login
                        </a>
                    </div>
                    <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                        <button type="button" class="btn btn-link text-muted text-decoration-none btn-sm" data-bs-dismiss="modal">
                            Malas, Nanti Saja
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="assets/js/city-wheel.js?v=3"></script>
    <script src="assets/subscribe.js?v=2"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Setup Data Kota
            const cityData = <?= json_encode($savedCities ?: []) ?>;
            const currentCityName = "<?= htmlspecialchars($city) ?>";
            
            // 2. Inisialisasi Wheel
            if (cityData.length > 0) {
                initCityWheel(cityData, currentCityName, 'dashboard.php?city=');
            }

            // 3. Listener Tombol Kota
            const btn = document.getElementById('dashboardWheelBtn');
            if(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openCityWheel();
                });
            }
        });

        // --- Fungsi Subscribe Notification (DIPERBARUI DENGAN SWEETALERT2) ---
        window.subscribeNotifications = async function() {
            const btn = document.getElementById('notifBtn');
            const originalContent = btn.innerHTML;
            
            try {
                // Efek Loading
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';
                btn.disabled = true;

                // Proses Subscribe
                const result = await window.registerAndSubscribe();
                
                // --- SUKSES ---
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Notifikasi cuaca harian telah diaktifkan.',
                    confirmButtonText: 'Mantap',
                    confirmButtonColor: '#198754', // Warna hijau
                    timer: 3000 // Otomatis tutup 3 detik
                });

                btn.innerHTML = '<i class="bi bi-bell-fill"></i> Aktif';
                btn.classList.remove('btn-white', 'text-primary', 'bg-white');
                btn.classList.add('btn-success', 'text-white');
                
            } catch (err) {
                // --- GAGAL ---
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: err.message,
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#dc3545' // Warna merah
                });

                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        };

        // --- Charts Configuration ---
        Chart.defaults.font.family = "'Poppins', sans-serif";
        Chart.defaults.color = '#666';

        // Chart Suhu
        <?php if ($weatherSummary && !empty($weatherSummary['trend_data'])): ?>
        new Chart(document.getElementById('temperatureChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($weatherSummary['trend_data'], 'date')) ?>,
                datasets: [{
                    label: 'Avg Temp',
                    data: <?= json_encode(array_column($weatherSummary['trend_data'], 'avg_temp')) ?>,
                    borderColor: '#4facfe',
                    backgroundColor: 'rgba(79, 172, 254, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
            }
        });
        <?php endif; ?>

        // Chart Kondisi
        <?php 
        $conditionStats = $weatherLog->getConditionStats($city, 30);
        if (!empty($conditionStats)):
            $rawLabels = array_column($conditionStats, 'weather_condition');
            $translateMap = [
                'Clouds' => 'Awan', 'Rain' => 'Hujan', 'Haze' => 'Kabut',
                'Thunderstorm' => 'Badai Petir', 'Mist' => 'Kabut Tipis', 'Clear' => 'Cerah',
                'Drizzle' => 'Gerimis', 'Snow' => 'Salju', 'Smoke' => 'Asap',
                'Fog' => 'Kabut', 'Squall' => 'Angin Kencang', 'Tornado' => 'Tornado'
            ];
            $labels_translated = array_map(function($l) use ($translateMap) {
                return $translateMap[$l] ?? $l;
            }, $rawLabels);
        ?>
        new Chart(document.getElementById('conditionsChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($labels_translated) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($conditionStats, 'count')) ?>,
                    backgroundColor: ['#4facfe', '#00f2fe', '#a8edea', '#fed6e3', '#ff9a9e'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { position: 'right', labels: { boxWidth: 10 } } }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
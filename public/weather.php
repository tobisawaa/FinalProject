<?php
require_once __DIR__ . '/../src/classes/ApiClientWeather.php';
require_once __DIR__ . '/../src/classes/Database.php';

$city = $_GET['city'] ?? 'Jakarta';
$weatherClient = new ApiClientWeather();

// Ambil Data Cuaca
$initialCurrent = $weatherClient->getCurrentWeather($city);
$initialForecast = $weatherClient->getForecast($city);

// Ambil Daftar Kota dari Database
$db = Database::getInstance()->getConnection();
$savedCities = $db->query("SELECT name FROM cities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Cek Suhu untuk tema
if ($initialCurrent && isset($initialCurrent['main']['temp'])) {
    $currentTemp = round($initialCurrent['main']['temp']);
} else {
    $currentTemp = 0; // Nilai default aman jika data kosong
}
$isHot = $currentTemp > 36;
$themeClass = $isHot ? 'theme-hot' : 'theme-cool';

// Fungsi Helper: Translate Hari ke Indonesia
function getIndoDay($timestamp) {
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $days[date('w', $timestamp)];
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Cuaca - <?= htmlspecialchars($city) ?></title>
  <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/img/logo.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/img/logo.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/weather.css">
  <link rel="stylesheet" href="assets/css/city-wheel.css">
  <script src="assets/js/city-wheel.js?v=3"></script>
  <style>
      /* --- ANIMATION KEYFRAMES --- */
      @keyframes fadeInUp {
          from { opacity: 0; transform: translateY(30px); }
          to { opacity: 1; transform: translateY(0); }
      }
      @keyframes float {
          0% { transform: translateY(0px); }
          50% { transform: translateY(-10px); }
          100% { transform: translateY(0px); }
      }
      /* --- APPLIED ANIMATIONS --- */
      .glass-header { animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
      .main-weather-card { opacity: 0; animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; animation-delay: 0.1s; }
      .forecast-section { opacity: 0; animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; animation-delay: 0.2s; }
      .daily-section { opacity: 0; animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; animation-delay: 0.3s; }
      .weather-icon-lg { animation: float 6s ease-in-out infinite; filter: drop-shadow(0 10px 15px rgba(0,0,0,0.2)); }
      .daily-row { transition: transform 0.2s, background-color 0.2s; }
      .daily-row:hover { transform: scale(1.02); background-color: rgba(255, 255, 255, 0.1); border-radius: 10px; cursor: default; }
  </style>
</head>
<body class="<?= $themeClass ?>"> 
    <div class="main-wrapper">
    
    <header class="glass-header">
        <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i> Dashboard</a>
        
        <div class="search-container">
            <button id="weatherWheelBtn" type="button" 
                    style="position: relative; z-index: 100; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 25px; border-radius: 50px; cursor: pointer; display: flex; align-items: center; gap: 10px; font-family: inherit; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <i class="bi bi-geo-alt-fill" style="font-size: 1.1rem;"></i>
                <span style="font-weight: 600; font-size: 1rem;"><?= htmlspecialchars($city) ?></span>
                <i class="bi bi-chevron-down" style="font-size: 0.8rem; opacity: 0.7;"></i>
            </button>
        </div>
    </header>

    <div class="content-grid">
        
        <section class="main-weather-card glass-card">
            <div class="weather-info-left">
                <h1 class="city-name"><?= htmlspecialchars($initialCurrent['name'] ?? $city) ?></h1>
                <p class="country-name">Indonesia</p>
                
                <div class="main-temp-wrapper">
                    <?php if($initialCurrent && isset($initialCurrent['weather'][0]['icon'])): ?>
                    <img src="http://openweathermap.org/img/wn/<?= $initialCurrent['weather'][0]['icon'] ?>@4x.png" alt="Icon" class="weather-icon-lg">
                    <?php endif; ?>
                    <div class="temp-text">
                        <span class="degree"><?= $currentTemp ?>°</span>
                        <span class="condition"><?= ucfirst($initialCurrent['weather'][0]['description'] ?? 'Data tidak tersedia') ?></span>
                    </div>
                </div>
            </div>

            <div class="weather-details-grid">
                <div class="detail-item">
                    <i class="bi bi-thermometer-half"></i>
                    <div>
                        <span class="label">Terasa</span>
                        <span class="value"><?= $initialCurrent && isset($initialCurrent['main']['feels_like']) ? round($initialCurrent['main']['feels_like']) : '-' ?>°C</span>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="bi bi-droplet-fill"></i>
                    <div>
                        <span class="label">Kelembaban</span>
                        <span class="value"><?= $initialCurrent && isset($initialCurrent['main']['humidity']) ? $initialCurrent['main']['humidity'] : '-' ?>%</span>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="bi bi-wind"></i>
                    <div>
                        <span class="label">Angin</span>
                        <span class="value"><?= $initialCurrent && isset($initialCurrent['wind']['speed']) ? $initialCurrent['wind']['speed'] : '-' ?> km/h</span>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="bi bi-eye-fill"></i>
                    <div>
                        <span class="label">Visibilitas</span>
                        <span class="value"><?= $initialCurrent && isset($initialCurrent['visibility']) ? round($initialCurrent['visibility']/1000, 1) : '-' ?> km</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="forecast-section glass-card">
            <h3>Prakiraan Per Jam</h3>
            <div class="hourly-scroll">
                <?php 
                $hourlyData = $initialForecast && isset($initialForecast['list']) ? array_slice($initialForecast['list'], 0, 8) : [];
                if(count($hourlyData) > 0):
                    foreach($hourlyData as $f): 
                ?>
                <div class="hourly-item">
                    <span class="time"><?= date('H:i', $f['dt']) ?></span>
                    <?php if(isset($f['weather'][0]['icon'])): ?>
                    <img src="http://openweathermap.org/img/wn/<?= $f['weather'][0]['icon'] ?>.png" alt="icon">
                    <?php endif; ?>
                    <span class="temp-small"><?= isset($f['main']['temp']) ? round($f['main']['temp']) : '-' ?>°</span>
                </div>
                <?php 
                    endforeach;
                else:
                ?>
                <p style="padding: 20px; text-align: center; color: rgba(255,255,255,0.6);">Data prakiraan tidak tersedia</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="daily-section glass-card">
            <h3>Prakiraan Mendatang</h3>
            <div class="daily-list">
                <?php 
                $dailyData = [];
                if($initialForecast && isset($initialForecast['list'])) {
                    $dailyData = array_filter($initialForecast['list'], function($item) {
                        return strpos($item['dt_txt'], '12:00:00') !== false;
                    });
                }
                if(count($dailyData) > 0):
                    foreach($dailyData as $d):
                ?>
                <div class="daily-row">
                    <span class="day-name"><?= getIndoDay($d['dt']) ?></span> 
                    
                    <div class="daily-icon-wrapper">
                        <?php if(isset($d['weather'][0]['icon'])): ?>
                        <img src="http://openweathermap.org/img/wn/<?= $d['weather'][0]['icon'] ?>.png" width="40">
                        <?php endif; ?>
                        <span class="daily-condition"><?= $d['weather'][0]['main'] ?? '-' ?></span>
                    </div>
                    <span class="daily-temp"><?= isset($d['main']['temp']) ? round($d['main']['temp']) : '-' ?>°C</span>
                </div>
                <?php 
                    endforeach;
                else:
                ?>
                <p style="padding: 20px; text-align: center; color: rgba(255,255,255,0.6);">Data prakiraan tidak tersedia</p>
                <?php endif; ?>
            </div>
        </section>

    </div>
  </div>

  <script src="city-wheel.js"></script>
  <script>
      document.addEventListener('DOMContentLoaded', () => {
          // Data kota aman dari null
          const cityData = <?= json_encode($savedCities ?: []) ?>;
          const currentCityName = "<?= htmlspecialchars($city) ?>";
          
          // Inisialisasi Wheel dengan base path khusus Weather
          if(cityData.length > 0) {
              initCityWheel(cityData, currentCityName, 'weather.php?city=');
          }
          
          const btn = document.getElementById('weatherWheelBtn');
          if(btn) {
              btn.addEventListener('click', (e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  openCityWheel();
              });
          }
      });
  </script>

</body>
</html>
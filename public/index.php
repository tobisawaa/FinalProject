<?php
require_once __DIR__ . '/../src/classes/Auth.php';
$auth = new Auth();

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Cuaca dan Aktivitas Harian</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    
    <div class="hero-section text-center">
        <div class="container animate-up">
            <div class="mb-4">
                <i class="bi bi-cloud-sun-fill display-1"></i>
            </div>
            <h1 class="display-4 fw-bold mb-3">Smart Weather & Activity</h1>
            <p class="lead mb-5" style="opacity: 0.9;">
                Optimalkan produktivitas harian Anda dengan rekomendasi aktivitas <br> 
                berbasis kecerdasan data cuaca real-time.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="login.php" class="btn btn-light btn-lg px-5 fw-bold text-primary shadow">Masuk</a>
                <a href="register.php" class="btn btn-outline-light btn-lg px-5 fw-bold">Daftar Akun</a>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-12 text-center mb-5 animate-up">
                <h2 class="fw-bold text-dark">Fitur Unggulan</h2>
                <p class="text-muted">Semua yang Anda butuhkan untuk merencanakan hari yang sempurna</p>
            </div>

            <div class="col-md-6 col-lg-3 mb-4 animate-up" style="animation-delay: 0.1s;">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon-box">
                            <i class="bi bi-thermometer-sun"></i>
                        </div>
                        <h5 class="card-title">Real-Time Weather</h5>
                        <p class="card-text text-muted small">Data presisi langsung dari OpenWeatherMap API untuk lokasi Anda.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4 animate-up" style="animation-delay: 0.2s;">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon-box">
                            <i class="bi bi-activity"></i>
                        </div>
                        <h5 class="card-title">Activity Manager</h5>
                        <p class="card-text text-muted small">Organisir jadwal Indoor & Outdoor dengan sistem manajemen cerdas.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4 animate-up" style="animation-delay: 0.3s;">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon-box">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <h5 class="card-title">Smart Alerts</h5>
                        <p class="card-text text-muted small">Notifikasi dini untuk perubahan cuaca ekstrem di sekitar Anda.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4 animate-up" style="animation-delay: 0.4s;">
                <div class="card h-100 text-center p-4">
                    <div class="card-body">
                        <div class="feature-icon-box">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h5 class="card-title">Analytics</h5>
                        <p class="card-text text-muted small">Insight mendalam tentang pola aktivitas dan kondisi lingkungan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 text-muted small mt-5">
        &copy; <?= date('Y') ?> Aplikasi Cuaca & Aktivitas Harian. All rights reserved.
    </footer>

</body>
</html>
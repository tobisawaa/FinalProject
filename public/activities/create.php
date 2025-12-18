<?php
require_once __DIR__ . '/../../src/classes/Auth.php';
require_once __DIR__ . '/../../src/classes/Activity.php';

$auth = new Auth();

if (!$auth->isLoggedIn() || $auth->isGuest()) {
    header('Location: ../login.php');
    exit;
}

$user = $auth->getCurrentUser();
$activity = new Activity();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'user_id' => $user['id'],
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
        'activity_type' => $_POST['activity_type'] ?? 'outdoor',
        'location' => $_POST['location'] ?? '',
        'scheduled_date' => $_POST['scheduled_date'] ?? '',
        'scheduled_time' => $_POST['scheduled_time'] ?? '',
        'status' => 'planned'
    ];
    
    try {
        $activity->create($data);
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $error = 'Gagal membuat aktivitas: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Aktivitas - WeatherApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="/FinalProjek/public/assets/img/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/FinalProjek/public/assets/img/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/FinalProjek/public/assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                
                <div class="d-flex align-items-center mb-3">
                    <a href="index.php" class="btn btn-light rounded-circle shadow-sm me-3 d-flex align-items-center justify-content-center p-0" 
                       style="width: 40px; height: 40px; min-width: 40px; min-height: 40px;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h4 class="fw-bold mb-0">Aktivitas Baru</h4>
                </div>

                <div class="form-card animate-up">
                    <div class="form-header">
                        <i class="bi bi-calendar-plus display-4 mb-2"></i>
                        <h5 class="mb-0">Rencanakan Harimu</h5>
                        <small class="opacity-75">Isi detail aktivitas di bawah ini</small>
                    </div>

                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger rounded-3 shadow-sm border-0">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Apa Rencanamu?</label>
                                <div class="mb-3">
                                    <input type="text" name="title" class="form-control form-control-lg fw-bold text-dark" 
                                           placeholder="Judul Kegiatan (Contoh: Jogging Pagi)" required>
                                </div>
                                <div class="mb-3">
                                    <textarea name="description" class="form-control" rows="3" placeholder="Catatan tambahan (Opsional)..."></textarea>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label text-secondary small fw-bold text-uppercase">Detail Pelaksanaan</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select name="activity_type" class="form-select" id="typeSelect" required>
                                                <option value="outdoor">Outdoor</option>
                                                <option value="indoor">Indoor</option>
                                            </select>
                                            <label for="typeSelect">Tipe</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="location" class="form-control" id="locInput" placeholder="Lokasi">
                                            <label for="locInput">Lokasi</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="date" name="scheduled_date" class="form-control" id="dateInput" required>
                                            <label for="dateInput">Tanggal</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="time" name="scheduled_time" class="form-control" id="timeInput">
                                            <label for="timeInput">Waktu</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow fw-bold">Simpan Aktivitas</button>
                                <a href="index.php" class="btn btn-link text-muted text-decoration-none">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
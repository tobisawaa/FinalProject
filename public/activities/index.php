<?php
require_once __DIR__ . '/../../src/classes/Auth.php';
require_once __DIR__ . '/../../src/classes/Activity.php';

$auth = new Auth();
// Mengecek user 
$user = $auth->getCurrentUser();
$activity = new Activity();

// Get filters
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$filters = [];
if ($typeFilter) $filters['activity_type'] = $typeFilter;
if ($statusFilter) $filters['status'] = $statusFilter;

// Get activities
if ($auth->isAdmin()) {
    $activities = $activity->getAll(null, $filters);
} else {
    $activities = $activity->getAll($user['id'], $filters);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Aktivitas - WeatherApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container px-4 mt-4 mb-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 animate-up">
            <div>
                <h2 class="fw-bold text-dark mb-0">Manajemen Aktivitas</h2>
                <p class="text-muted mb-0">Kelola jadwal dan kegiatan harianmu.</p>
            </div>
            <?php if (!$auth->isGuest()): ?>
                <a href="create.php" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-bold">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Aktivitas
                </a>
            <?php endif; ?>
        </div>

        <div class="action-bar animate-up" style="animation-delay: 0.1s;">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-filter text-primary"></i></span>
                        <select name="type" class="form-select border-start-0 bg-light" onchange="this.form.submit()">
                            <option value="">Semua Tipe</option>
                            <option value="outdoor" <?= $typeFilter === 'outdoor' ? 'selected' : '' ?>>Outdoor</option>
                            <option value="indoor" <?= $typeFilter === 'indoor' ? 'selected' : '' ?>>Indoor</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-check-circle text-success"></i></span>
                        <select name="status" class="form-select border-start-0 bg-light" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="planned" <?= $statusFilter === 'planned' ? 'selected' : '' ?>>Direncanakan</option>
                            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Selesai</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <?php if($typeFilter || $statusFilter): ?>
                        <a href="index.php" class="btn btn-outline-danger btn-sm rounded-pill px-3"><i class="bi bi-x-lg"></i> Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="animate-up" style="animation-delay: 0.2s;">
            <?php if (empty($activities)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-clipboard-x display-1 text-muted opacity-25"></i>
                    </div>
                    <h4 class="text-muted">Tidak ada aktivitas ditemukan</h4>
                    <p class="text-muted small">Coba ubah filter atau buat aktivitas baru.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Aktivitas</th>
                                <th>Lokasi</th>
                                <th>Jadwal</th>
                                <th>Status</th>
                                <?php if ($auth->isAdmin()): ?><th>User</th><?php endif; ?>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $act): ?>
                                <?php 
                                    $iconType = $act['activity_type'] === 'outdoor' ? 'bi-tree-fill text-success' : 'bi-house-heart-fill text-info';
                                    $bgIcon = $act['activity_type'] === 'outdoor' ? 'bg-success bg-opacity-10' : 'bg-info bg-opacity-10';
                                    
                                    $statusClass = match($act['status']) {
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'warning text-dark'
                                    };
                                    $statusLabel = match($act['status']) {
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Batal',
                                        'planned' => 'Rencana'
                                    };
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="icon-box <?= $bgIcon ?>">
                                                <i class="bi <?= $iconType ?>"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($act['title']) ?></div>
                                                <div class="small text-muted text-truncate" style="max-width: 200px;"><?= htmlspecialchars($act['description'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 text-secondary">
                                            <i class="bi bi-geo-alt small"></i> 
                                            <?= htmlspecialchars($act['location']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= date('d M Y', strtotime($act['scheduled_date'])) ?></div>
                                        <div class="small text-muted"><?= date('H:i', strtotime($act['scheduled_time'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= $statusClass ?>">
                                            <?= $statusLabel ?>
                                        </span>
                                    </td>
                                    <?php if ($auth->isAdmin()): ?>
                                        <td><small class="text-muted"><i class="bi bi-person"></i> <?= htmlspecialchars($act['user_name']) ?></small></td>
                                    <?php endif; ?>
                                    <td class="text-end pe-4">
                                        <?php if (!$auth->isGuest()): ?>
                                        <div class="btn-group">
                                            <a href="edit.php?id=<?= $act['id'] ?>" class="btn btn-light btn-sm text-primary shadow-sm" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            
                                            <a href="delete.php?id=<?= $act['id'] ?>" class="btn btn-light btn-sm text-danger shadow-sm btn-delete" 
                                               title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-delete');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Mencegah link langsung pindah
                    
                    const href = this.getAttribute('href');
                    
                    Swal.fire({
                        title: 'Yakin hapus aktivitas?',
                        text: "Data yang dihapus tidak dapat dikembalikan!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545', // Warna merah bootstrap
                        cancelButtonColor: '#6c757d',  // Warna abu bootstrap
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                        reverseButtons: true, // Tombol batal di kiri, hapus di kanan (opsional)
                        background: '#fff',
                        borderRadius: '15px'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Jika user klik Ya, arahkan ke delete.php
                            window.location.href = href;
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>

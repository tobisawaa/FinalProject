<?php
// MUNDUR 1 LANGKAH (../) karena folder admin sejajar dengan src
require_once __DIR__ . '/../src/classes/Auth.php';
require_once __DIR__ . '/../src/classes/Database.php';

$auth = new Auth();

if (!$auth->isAdmin()) {
    header('Location: ../public/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user = $auth->getCurrentUser();

// Get statistics
$userCount = $db->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
$adminCount = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch()['count'];
$totalActivities = $db->query("SELECT COUNT(*) as count FROM activities")->fetch()['count'];

// Get Data Lists
$allUsers = $db->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$recentActivities = $db->query("SELECT a.*, u.name as user_name FROM activities a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10")->fetchAll();
$allCities = $db->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'delete_user':
                $userId = (int)$_POST['user_id'];
                if ($userId !== $user['id']) {
                    $db->exec("DELETE FROM activities WHERE user_id = $userId");
                    $db->exec("DELETE FROM users WHERE id = $userId");
                }
                break;
            
            case 'add_city':
                $cityName = trim($_POST['city_name']);
                $province = trim($_POST['province']);
                // API KEY
                $apiKey = "34bdc3f417e1a41c30656d1410e21b42"; 

                if(!empty($cityName) && !empty($province)) {
                    $url = "http://api.openweathermap.org/geo/1.0/direct?q=" . urlencode($cityName) . "&limit=1&appid=" . $apiKey;
                    @$json = file_get_contents($url);
                    if ($json) {
                        $data = json_decode($json, true);
                        if (!empty($data)) {
                            $lat = $data[0]['lat'];
                            $lon = $data[0]['lon'];
                            $stmt = $db->prepare("INSERT INTO cities (name, province, lat, lon) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$cityName, $province, $lat, $lon]);
                        }
                    }
                }
                break;

            case 'delete_city':
                $cityId = (int)$_POST['city_id'];
                $db->exec("DELETE FROM cities WHERE id = $cityId");
                break;
        }
    } catch (Exception $e) {}
    
    echo "<script>window.location.href='admin_dashboard.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Console</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/FinalProjek/public/assets/img/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/FinalProjek/public/assets/img/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/FinalProjek/public/assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body class="admin-dashboard">

    <div class="sidebar d-flex flex-column">
        <div class="sidebar-brand">
            <i class="bi bi-shield-lock-fill text-success"></i>
            <span>AdminPanel</span>
        </div>
        
        <nav class="nav flex-column mt-3">
            <a class="nav-link-admin active" href="#dashboard" data-bs-toggle="tab">
                <i class="bi bi-grid-1x2-fill"></i> <span>Overview</span>
            </a>
            <a class="nav-link-admin" href="#users" data-bs-toggle="tab">
                <i class="bi bi-people-fill"></i> <span>Users</span>
            </a>
            <a class="nav-link-admin" href="#activities" data-bs-toggle="tab">
                <i class="bi bi-activity"></i> <span>Monitoring</span>
            </a>
            <a class="nav-link-admin" href="#cities" data-bs-toggle="tab">
                <i class="bi bi-map-fill"></i> <span>Kelola Kota</span>
            </a>
        </nav>

        <div class="mt-auto p-3 border-top border-secondary">
            <div class="d-flex align-items-center gap-2 mb-3 px-2 text-white">
                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div class="small fw-bold sidebar-footer"><?= htmlspecialchars($user['name']) ?></div>
            </div>
            <a href="../public/logout.php" class="btn btn-outline-danger w-100 btn-sm d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-box-arrow-right"></i> <span class="sidebar-footer">Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="dashboard">
                <h3 class="fw-bold mb-4 text-white">Dashboard Overview</h3>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-icon-primary"><i class="bi bi-people"></i></div>
                            <h2 class="fw-bold mb-0 text-white"><?= $userCount ?></h2>
                            <span class="text-muted small">Total Users</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-icon-success"><i class="bi bi-geo-alt"></i></div>
                            <h2 class="fw-bold mb-0 text-white"><?= count($allCities) ?></h2>
                            <span class="text-muted small">Kota Aktif</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-icon-warning"><i class="bi bi-shield-check"></i></div>
                            <h2 class="fw-bold mb-0 text-white"><?= $adminCount ?></h2>
                            <span class="text-muted small">Administrators</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-icon-light text-dark"><i class="bi bi-list-check"></i></div>
                            <h2 class="fw-bold mb-0 text-white"><?= $totalActivities ?></h2>
                            <span class="text-muted small">Total Activities</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="users">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0 text-white">Manajemen Pengguna</h3>
                    <span class="badge bg-primary rounded-pill"><?= count($allUsers) ?> Registered</span>
                </div>
                <div class="card-table">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>User</th><th>Role</th><th>Bergabung</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($allUsers as $u): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-white"><?= htmlspecialchars($u['name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td><span class="badge <?= $u['role'] === 'admin' ? 'bg-danger' : 'bg-success' ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                <td class="text-end">
                                    <?php if ($u['id'] !== $user['id']): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus user?')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="activities">
                <h3 class="fw-bold mb-4 text-white">Log Aktivitas Terbaru</h3>
                <div class="card-table">
                    <table class="table admin-table mb-0">
                        <thead><tr><th>User</th><th>Kegiatan</th><th>Waktu</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentActivities as $act): ?>
                                <tr>
                                    <td class="fw-bold text-white"><?= htmlspecialchars($act['user_name']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($act['title'] ?? '-') ?></td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($act['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="cities">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card border-0 shadow-sm">
                            <h5 class="fw-bold text-white mb-3">Tambah Kota</h5>
                            <p class="text-muted small mb-4">Otomatis cari Lat/Lon via API</p>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="add_city">
                                <div class="mb-3">
                                    <label class="form-label">Nama Kota</label>
                                    <input type="text" name="city_name" class="form-control" placeholder="Contoh: Surabaya" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Provinsi</label>
                                    <input type="text" name="province" class="form-control" placeholder="Contoh: Jawa Timur" required>
                                </div>
                                <button type="submit" class="btn btn-emerald w-100">Simpan Kota</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card-table">
                            <div class="table-responsive" style="max-height: 600px;">
                                <table class="table admin-table mb-0">
                                    <thead class="sticky-top bg-dark"><tr><th>Kota</th><th>Lat/Lon</th><th class="text-end">Aksi</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($allCities as $city): ?>
                                        <tr>
                                            <td class="text-white">
                                                <div class="fw-bold"><?= htmlspecialchars($city['name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($city['province']) ?></div>
                                            </td>
                                            <td class="text-muted font-monospace small"><?= $city['lat'] ?>, <?= $city['lon'] ?></td>
                                            <td class="text-end">
                                                <form method="POST" onsubmit="return confirm('Hapus?')">
                                                    <input type="hidden" name="action" value="delete_city">
                                                    <input type="hidden" name="city_id" value="<?= $city['id'] ?>">
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
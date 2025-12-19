<?php 
require_once __DIR__ . '/../../src/classes/Auth.php';
require_once __DIR__ . '/../../src/classes/Database.php';
require_once __DIR__ . '/../../src/classes/AnalyticsService.php';

$auth = new Auth();
$isGuest = $auth->isGuest();
$isAdmin = $auth->isAdmin();
$isLoggedIn = $auth->isLoggedIn();

$navStats = [];
if ($isLoggedIn && !$isGuest) {
    $user = $auth->getCurrentUser();
    $analyticsNav = new AnalyticsService();
    $navStatsRaw = $analyticsNav->getUserActivityStats($user['id']);
    
    $totalNav = $navStatsRaw['total_activities'];
    $navStats = [
        'total' => $totalNav,
        'completed' => ($totalNav > 0) ? ($navStatsRaw['completed_count'] / $totalNav) * 100 : 0,
        'planned' => ($totalNav > 0) ? ($navStatsRaw['planned_count'] / $totalNav) * 100 : 0,
        'cancelled' => ($totalNav > 0) ? ($navStatsRaw['cancelled_count'] / $totalNav) * 100 : 0,
    ];
}
?>

<style>
    /* --- Existing Navbar Styles --- */
    body { padding-top: 85px; }
    .glass-nav {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
        padding: 10px 0;
        transition: all 0.3s ease;
        z-index: 1050;
    }
    .navbar-brand img { transition: transform 0.3s; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); }
    .navbar-brand:hover img { transform: scale(1.05) rotate(-5deg); }
    .brand-text {
        font-family: 'Poppins', sans-serif; color: #333; font-weight: 700; font-size: 1.3rem; letter-spacing: -0.5px;
        background: linear-gradient(135deg, #333 0%, #555 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .nav-link { color: #555; font-weight: 500; font-size: 0.95rem; padding: 8px 15px !important; border-radius: 50px; transition: 0.3s; }
    .nav-link:hover, .nav-link.active { color: #007bff; background: rgba(0,123,255,0.05); }
    
    .profile-btn { 
        border: 1px solid #eee; background: white; padding: 5px 12px 5px 5px; 
        border-radius: 50px; display: flex; align-items: center; gap: 10px; 
        cursor: pointer; transition: all 0.3s; max-width: 200px;
    }
    .profile-btn:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-color: #ddd; }
    
    .nav-avatar { 
        width: 35px; height: 35px; min-width: 35px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        color: white; border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; 
        font-weight: bold; overflow: hidden;
    }
    .nav-avatar img { width: 100%; height: 100%; object-fit: cover; }
    
    .dropdown-menu { border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border-radius: 15px; margin-top: 10px; padding: 10px; }
    .guest-pill { background: #f1f5f9; color: #64748b; padding: 8px 16px; border-radius: 30px; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    
    /* --- NEW: Profile Crop Modal Styles --- */
    .custom-crop-modal .modal-dialog { max-width: 800px; }
    .custom-crop-modal .modal-content { border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
    .custom-crop-modal .modal-header { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid #dee2e6; padding: 15px 25px; }
    .custom-crop-modal .modal-title { font-weight: 700; color: #333; font-size: 1.1rem; }
    
    .crop-layout { display: flex; flex-wrap: wrap; background-color: #212529; }
    .crop-main { flex: 1; position: relative; height: 400px; border-right: 1px solid #333; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .crop-main img { max-width: 100%; max-height: 100%; display: block; }
    
    .crop-sidebar { width: 250px; background: #fff; padding: 25px; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10; }
    .preview-label { font-size: 0.85rem; font-weight: 600; color: #6c757d; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; }
    .preview-box { width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 4px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.15); background-color: #f8f9fa; }

    .modal-footer-custom { padding: 15px 25px; background: #fff; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
    .btn-cancel { background: #f1f3f5; color: #495057; border: none; font-weight: 600; border-radius: 10px; padding: 8px 20px; transition: 0.2s; }
    .btn-cancel:hover { background: #e9ecef; }
    .btn-save { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none; font-weight: 600; border-radius: 10px; padding: 8px 25px; box-shadow: 0 4px 10px rgba(79, 172, 254, 0.3); transition: transform 0.2s; }
    .btn-save:hover { transform: translateY(-2px); color: white; }
    .btn-save:disabled { opacity: 0.7; transform: none; cursor: not-allowed; }

    @media (max-width: 991px) {
        body { padding-top: 70px; } .glass-nav { padding: 10px 0; } .brand-text { font-size: 1.1rem; }
        .navbar-brand img { width: 32px; height: 32px; } .nav-link { font-size: 0.9rem; padding: 8px 12px !important; }
        .navbar-nav { padding: 10px 0; } .nav-item { margin-bottom: 5px; } 
        .profile-btn { padding: 4px 10px 4px 4px; font-size: 0.9rem; width: auto; display: inline-flex; } 
        .nav-avatar { width: 32px; height: 32px; font-size: 0.85rem; }
    }
    @media (max-width: 768px) {
        .custom-crop-modal .modal-dialog { margin: 10px; }
        .crop-layout { flex-direction: column; }
        .crop-main { height: 300px; border-right: none; border-bottom: 1px solid #333; }
        .crop-sidebar { width: 100%; flex-direction: row; justify-content: space-between; padding: 15px; }
        .preview-box { width: 70px; height: 70px; margin-bottom: 0; }
        .preview-label { display: none; }
    }
    @media (max-width: 480px) {
        body { padding-top: 65px; } .container-fluid { padding-left: 15px; padding-right: 15px; }
        .navbar-brand { gap: 8px !important; } .brand-text { font-size: 1rem; display: block; } 
        .navbar-toggler { padding: 0; border: none; } .navbar-toggler i { font-size: 1.5rem; }
    }
</style>

<nav class="navbar navbar-expand-lg fixed-top glass-nav">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/FinalProjek/public/dashboard.php">
            <img src="/FinalProjek/public/assets/img/logo.png" alt="Logo" width="38" height="38">
            <span class="brand-text">WeatherApp</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="bi bi-list fs-2 text-dark"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="/FinalProjek/public/dashboard.php"><i class="bi bi-grid-fill me-1"></i> Dashboard</a></li>
                <?php if (!$isGuest): ?>
                    <li class="nav-item"><a class="nav-link" href="/FinalProjek/public/activities/index.php">Aktivitas</a></li>
                    <li class="nav-item"><a class="nav-link" href="/FinalProjek/public/weatherlogs/index.php">Catatan</a></li>
                    <li class="nav-item"><a class="nav-link" href="/FinalProjek/public/weatherlogs/analytics.php">Analisis</a></li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav align-items-center gap-3">
                <?php if ($isGuest): $guestName = $_SESSION['guest_name'] ?? 'Guest'; ?>
                    <li class="nav-item"><div class="guest-pill"><i class="bi bi-incognito"></i> <?= htmlspecialchars($guestName) ?></div></li>
                    <li class="nav-item"><a class="btn btn-danger btn-sm rounded-pill px-3" href="/FinalProjek/public/logout.php">Logout</a></li>
                <?php elseif ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <div class="profile-btn" data-bs-toggle="dropdown">
                            <div class="nav-avatar">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="/FinalProjek/public/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Avatar">
                                <?php else: ?>
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div class="lh-1 text-start ms-2 me-1">
                                <div class="fw-bold text-dark small" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px;">
                                    <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?> </div>
                                <div class="text-muted d-none d-md-block" style="font-size: 10px;">User Account</div>
                            </div>
                            <i class="bi bi-chevron-down small text-muted ms-1"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end animate-slide-down">
                            <li><button class="dropdown-item py-2 d-flex align-items-center gap-2 rounded" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="bi bi-person-badge text-primary"></i> Profil Saya</button></li>
                            <?php if ($isAdmin): ?>
                                <li><a class="dropdown-item py-2 d-flex align-items-center gap-2 rounded" href="/FinalProjek/admin/admin_dashboard.php"><i class="bi bi-shield-lock text-danger"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2 rounded text-danger" href="/FinalProjek/public/logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if ($isLoggedIn && !$isGuest): ?>
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 p-0 position-relative" style="height: 120px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-0">
                <div class="d-flex justify-content-center position-relative" style="margin-top: -50px;">
                    <div class="nav-avatar" id="profileLargeAvatar" style="width: 100px; height: 100px; font-size: 2.5rem; border: 4px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="/FinalProjek/public/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Avatar" style="width:100%;height:100%;">
                        <?php else: ?>
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-sm btn-light border position-absolute rounded-circle shadow-sm" id="btnChangeAvatar" style="bottom: 0; right: 35%; width: 30px; height: 30px; padding: 0;" title="Ganti Foto">
                        <i class="bi bi-camera-fill text-muted small"></i>
                    </button>
                </div>

                <div class="mt-3 mb-4">
                    <div id="profileView" class="text-center">
                        <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($user['name']) ?></h4>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></p>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 mt-1" id="btnToggleEdit">
                            <i class="bi bi-pencil-square me-1"></i> Edit Profil
                        </button>
                    </div>

                    <form id="profileEditForm" class="d-none text-start p-3 bg-light rounded-3 border mt-3">
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-muted">Username</label>
                            <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-muted">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-muted">Password Baru <span class="text-muted fw-normal">(Opsional)</span></label>
                            <div class="input-group input-group-sm">
                                <input type="password" name="password" id="editPass" class="form-control" placeholder="Kosongkan jika tidak ubah">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePass('editPass')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Konfirmasi Password</label>
                            <div class="input-group input-group-sm">
                                <input type="password" name="confirm_password" id="editConfirmPass" class="form-control" placeholder="Ulangi password baru">
                            </div>
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-sm btn-secondary" id="btnCancelEdit">Batal</button>
                            <button type="submit" class="btn btn-sm btn-primary" id="btnSaveProfile">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>

                <div class="bg-light rounded-4 p-3 border border-light">
                    <h6 class="fw-bold mb-3 small text-muted text-uppercase text-center">Statistik Aktivitas</h6>
                    <div class="progress mb-3" style="height: 6px;"><div class="progress-bar" style="width: 100%"></div></div>
                    <div class="row g-2 text-center">
                        <div class="col-4"><div class="p-2 bg-white rounded border"><div class="text-success fw-bold small"><?= number_format($navStats['completed'],0) ?>%</div><div class="text-muted" style="font-size:10px">Selesai</div></div></div>
                        <div class="col-4"><div class="p-2 bg-white rounded border"><div class="text-warning fw-bold small"><?= number_format($navStats['planned'],0) ?>%</div><div class="text-muted" style="font-size:10px">Rencana</div></div></div>
                        <div class="col-4"><div class="p-2 bg-white rounded border"><div class="text-danger fw-bold small"><?= number_format($navStats['cancelled'],0) ?>%</div><div class="text-muted" style="font-size:10px">Batal</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade custom-crop-modal" id="cropModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sesuaikan Foto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="crop-layout">
                    <div class="crop-main">
                        <img id="cropImage" src="" alt="Picture">
                    </div>
                    <div class="crop-sidebar">
                        <div class="preview-label">Preview</div>
                        <div class="preview-box"></div> </div>
                </div>
            </div>
            <div class="modal-footer-custom">
                <input type="file" id="inputAvatarFile" accept="image/*" style="display: none;">
                <button class="btn btn-cancel" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-save" id="btnUploadCropped">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // --- Logic Toggle Password Visibility ---
    function togglePass(id) {
        const x = document.getElementById(id);
        if (x.type === "password") x.type = "text"; else x.type = "password";
    }

    // Logic Navbar Avatar & Profile Edit
    document.addEventListener('DOMContentLoaded', () => {
        
        // --- LOGIC EDIT PROFILE ---
        const btnToggleEdit = document.getElementById('btnToggleEdit');
        const btnCancelEdit = document.getElementById('btnCancelEdit');
        const profileView = document.getElementById('profileView');
        const profileEditForm = document.getElementById('profileEditForm');
        
        if (btnToggleEdit) {
            btnToggleEdit.addEventListener('click', () => {
                profileView.classList.add('d-none');
                profileEditForm.classList.remove('d-none');
            });
        }

        if (btnCancelEdit) {
            btnCancelEdit.addEventListener('click', () => {
                profileEditForm.classList.add('d-none');
                profileView.classList.remove('d-none');
                profileEditForm.reset(); // Reset form jika batal
            });
        }

        if (profileEditForm) {
            profileEditForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btnSave = document.getElementById('btnSaveProfile');
                const originalText = btnSave.innerHTML;
                
                // Cek Password Match di Client Side
                const p1 = document.getElementById('editPass').value;
                const p2 = document.getElementById('editConfirmPass').value;
                if(p1 && p1 !== p2) {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: 'Konfirmasi password tidak cocok!' });
                    return;
                }

                btnSave.disabled = true;
                btnSave.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Simpan...';

                try {
                    const formData = new FormData(profileEditForm);
                    const res = await fetch('/FinalProjek/public/update_profile.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if(data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
                        btnSave.disabled = false;
                        btnSave.innerHTML = originalText;
                    }
                } catch (err) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan koneksi.' });
                    btnSave.disabled = false;
                    btnSave.innerHTML = originalText;
                }
            });
        }


        // --- LOGIC CROPPER & UPLOAD (EXISTING) ---
        let cropper = null;
        const btnChange = document.getElementById('btnChangeAvatar');
        const inputFile = document.getElementById('inputAvatarFile');
        const cropImage = document.getElementById('cropImage');
        const cropModalEl = document.getElementById('cropModal');
        const cropModal = new bootstrap.Modal(cropModalEl);
        const btnUpload = document.getElementById('btnUploadCropped');

        if (btnChange) {
            btnChange.addEventListener('click', () => { inputFile.click(); });

            inputFile.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (evt) => {
                        cropImage.src = evt.target.result;
                        cropModal.show();
                    };
                    reader.readAsDataURL(file);
                }
            });

            cropModalEl.addEventListener('shown.bs.modal', () => {
                if(cropper) cropper.destroy();
                cropper = new Cropper(cropImage, { 
                    aspectRatio: 1, 
                    viewMode: 1,
                    preview: '.preview-box' // Menambahkan fitur preview ke class box
                });
            });

            btnUpload.addEventListener('click', async () => {
                if(!cropper) return;
                const originalText = btnUpload.innerText;
                btnUpload.disabled = true; 
                btnUpload.innerText = "Menyimpan...";
                
                const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
                const base64 = canvas.toDataURL('image/jpeg', 0.9);

                try {
                    const formData = new FormData(); formData.append('image', base64);
                    const res = await fetch('/FinalProjek/public/upload_profile_picture.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.status === 'success') {
                        location.reload(); 
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
                        btnUpload.disabled = false;
                        btnUpload.innerText = originalText;
                    }
                } catch (err) { 
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error koneksi' });
                    btnUpload.disabled = false;
                    btnUpload.innerText = originalText;
                }
            });
        }
    });
</script>
<?php endif; ?>

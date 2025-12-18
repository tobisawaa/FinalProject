<?php
require_once __DIR__ . '/../src/classes/Auth.php';
require_once __DIR__ . '/../src/classes/EmailVerification.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$auth = new Auth();
$emailVerify = new EmailVerification();

// --- AJAX HANDLER (Kirim OTP) ---
if (isset($_POST['action']) && $_POST['action'] === 'send_otp') {
    header('Content-Type: application/json');
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? 'User';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']); exit;
    }
    $checkEmail = $auth->getDb()->query("SELECT id FROM users WHERE email = '$email'");
    if ($checkEmail->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar. Silakan login.']); exit;
    }

    $otp = $emailVerify->generateOTP();
    if ($emailVerify->sendVerificationEmail($email, $name, $otp)) {
        $emailVerify->storeOTP($email, $otp, 'register');
        echo json_encode(['status' => 'success', 'message' => 'Kode OTP terkirim ke email Anda!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal kirim OTP. Cek koneksi internet.']);
    }
    exit;
}

// --- REGISTER HANDLER ---
if ($auth->isLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $otpInput = $_POST['otp'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $detectedCity = !empty($_POST['detected_city']) ? $_POST['detected_city'] : 'Jakarta';
    $croppedImageData = $_POST['cropped_image'] ?? ''; // Data Base64 dari CropperJS

    if (empty($name) || empty($email) || empty($password) || empty($otpInput)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        if ($emailVerify->verifyOTP($email, $otpInput, 'register')) {
            $result = $auth->register($name, $email, $password);
            
            if ($result['success']) {
                $userId = $result['user_id'];
                $auth->getDb()->query("UPDATE users SET is_verified = 1 WHERE email = ?", [$email]);
                
                // --- PROSES SIMPAN GAMBAR HASIL CROP (BASE64) ---
                if (!empty($croppedImageData)) {
                    $data = explode(',', $croppedImageData);
                    if (count($data) == 2) {
                        $imageData = base64_decode($data[1]);
                        $fileName = 'profile_' . $userId . '_' . time() . '.png';
                        $uploadDir = __DIR__ . '/assets/img/uploads/';
                        
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        
                        if (file_put_contents($uploadDir . $fileName, $imageData)) {
                            $dbPath = 'assets/img/uploads/' . $fileName;
                            $auth->getDb()->query("UPDATE users SET profile_picture = ? WHERE id = ?", [$dbPath, $userId]);
                        }
                    }
                }
                // ------------------------------------------------

                $_SESSION['selected_city'] = $detectedCity; 
                $success = 'Registrasi Berhasil! Mengalihkan...';
                echo "<script>setTimeout(() => { window.location.href = 'login.php'; }, 2000);</script>";
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Kode OTP salah atau kadaluarsa.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun - WeatherApp</title>
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
    <link rel="stylesheet" href="assets/css/register.css">
    <style>
        /* Styling Khusus Upload Foto */
        .profile-upload-wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .profile-upload-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #f0f7ff;
            border: 2px dashed #4facfe;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
            position: relative;
            transition: all 0.3s;
        }
        .profile-upload-circle:hover {
            background-color: #e0efff;
            border-color: #00f2fe;
        }
        .profile-upload-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none; /* Sembunyikan default */
        }
        .upload-placeholder {
            text-align: center;
            color: #4facfe;
        }
        .upload-placeholder i {
            font-size: 1.5rem;
            display: block;
        }
        .upload-placeholder span {
            font-size: 0.7rem;
            font-weight: 600;
        }
        /* Mobile adjustment for cropper modal */
        #cropperImage { max-width: 100%; max-height: 70vh; display: block; }
    </style>
</head>
<body>

    <div class="glass-card animate-up">
        <div class="left-panel d-none d-md-flex">
            <i class="bi bi-cloud-sun-fill display-1 mb-3"></i>
            <h2 class="fw-bold">WeatherApp</h2>
            <p class="opacity-75">Bergabunglah dan atur aktivitas harianmu berdasarkan cuaca di sekitarmu.</p>
        </div>

        <div class="right-panel">
            <h3 class="fw-bold text-dark mb-4">Buat Akun Baru</h3>
            
            <div id="alertArea">
                <?php if ($error): ?><div class="alert alert-danger rounded-3 border-0 shadow-sm mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success rounded-3 border-0 shadow-sm mb-3"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>
            </div>

            <form method="POST" id="registerForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="detected_city" id="detectedCityInput" value="">
                
                <input type="hidden" name="cropped_image" id="croppedImageInput"> 
                <input type="file" id="realFileInput" accept="image/*" style="display: none;">

                <div class="profile-upload-wrapper">
                    <div class="profile-upload-circle" onclick="document.getElementById('realFileInput').click()">
                        <img id="previewAvatar" src="" alt="Avatar">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <i class="bi bi-camera-fill"></i>
                            <span>Upload Foto</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <input type="text" name="name" id="inputName" class="form-control" placeholder="Nama Lengkap" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
                </div>
                
                <div class="mb-3">
                    <input type="email" name="email" id="inputEmail" class="form-control" placeholder="Alamat Email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" name="otp" class="form-control" placeholder="Kode OTP (6 Digit)" maxlength="6" pattern="[0-9]{6}" required>
                        <button class="btn btn-outline-secondary" type="button" id="btnSendOtp" onclick="sendOtp()">
                            <span id="btnText">Kirim OTP</span>
                        </button>
                    </div>
                    <div class="form-text small" id="otpHelp">Klik "Kirim OTP" dan cek email Anda.</div>
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <div class="input-group">
                            <input type="password" name="password" id="regPass" class="form-control border-end-0" placeholder="Password" required minlength="6">
                            <span class="input-group-text bg-light border-start-0" style="cursor: pointer;" onclick="togglePassword('regPass', this)"><i class="bi bi-eye-slash"></i></span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="regConfirmPass" class="form-control border-end-0" placeholder="Ulangi Pass" required>
                            <span class="input-group-text bg-light border-start-0" style="cursor: pointer;" onclick="togglePassword('regConfirmPass', this)"><i class="bi bi-eye-slash"></i></span>
                        </div>
                    </div>
                </div>
                
                <div class="location-box" id="locationBox">
                    <div class="location-info">
                        <i class="bi bi-geo-alt-fill text-primary" id="locIcon"></i>
                        <div><strong id="locTitle">Lokasi Default</strong><div class="small" id="locStatus">Jakarta (Default)</div></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="detectLocation()" id="btnDetect"><i class="bi bi-crosshair"></i> Aktifkan</button>
                </div>

                <button type="submit" class="btn-gradient w-100 py-2 shadow-sm">Daftar Sekarang</button>
                <div class="text-center mt-4"><p class="text-muted small">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-bold text-primary">Login disini</a></p></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="cropModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sesuaikan Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div style="max-height: 70vh;">
                        <img id="cropperImage" src="" alt="Picture">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnCrop">Potong & Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // --- LOGIKA CROPPER JS ---
        let cropper;
        const fileInput = document.getElementById('realFileInput');
        const cropperImage = document.getElementById('cropperImage');
        const cropModalEl = document.getElementById('cropModal');
        const cropModal = new bootstrap.Modal(cropModalEl);
        const previewAvatar = document.getElementById('previewAvatar');
        const placeholder = document.getElementById('uploadPlaceholder');
        const croppedInput = document.getElementById('croppedImageInput');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    cropperImage.src = e.target.result;
                    cropModal.show();
                }
                reader.readAsDataURL(file);
            }
        });

        cropModalEl.addEventListener('shown.bs.modal', function () {
            if(cropper) cropper.destroy();
            cropper = new Cropper(cropperImage, {
                aspectRatio: 1, // Wajib Persegi 1:1
                viewMode: 1,
                autoCropArea: 1,
            });
        });

        document.getElementById('btnCrop').addEventListener('click', function() {
            if(cropper) {
                const canvas = cropper.getCroppedCanvas({ width: 500, height: 500 });
                const base64data = canvas.toDataURL('image/jpeg', 0.8);
                
                previewAvatar.src = base64data;
                previewAvatar.style.display = 'block';
                placeholder.style.display = 'none';
                croppedInput.value = base64data;
                cropModal.hide();
            }
        });

        // --- FUNGSI UTILITY ---
        function togglePassword(inputId, iconSpan) {
            const input = document.getElementById(inputId);
            const icon = iconSpan.querySelector('i');
            if (input.type === "password") {
                input.type = "text"; icon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                input.type = "password"; icon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        }

        async function detectLocation() {
            const btn = document.getElementById('btnDetect');
            const status = document.getElementById('locStatus');
            const box = document.getElementById('locationBox');
            const input = document.getElementById('detectedCityInput');
            const icon = document.getElementById('locIcon');

            if (!navigator.geolocation) { 
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Didukung',
                    text: 'Browser Anda tidak mendukung Geolocation.',
                    confirmButtonColor: '#4facfe'
                });
                return; 
            }
            
            btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; status.innerText = "Mendeteksi...";

            navigator.geolocation.getCurrentPosition(async (position) => {
                const lat = position.coords.latitude; const lon = position.coords.longitude;
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`);
                    const data = await response.json();
                    let city = data.address.city || data.address.town || data.address.county || 'Jakarta';
                    city = city.replace(/Kota /i, '').replace(/Kabupaten /i, '');
                    input.value = city; status.innerText = `${city} (Terdeteksi)`;
                    box.classList.add('location-active'); icon.className = "bi bi-check-circle-fill text-success";
                    btn.innerHTML = '<i class="bi bi-check"></i> Aktif';
                } catch (error) { status.innerText = "Gagal deteksi, default Jakarta."; btn.disabled = false; btn.innerHTML = 'Coba Lagi'; }
            }, () => { status.innerText = "Akses ditolak. Default: Jakarta"; btn.disabled = false; btn.innerHTML = 'Aktifkan'; });
        }

        async function sendOtp() {
            const email = document.getElementById('inputEmail').value;
            const name = document.getElementById('inputName').value;
            const btn = document.getElementById('btnSendOtp');
            const btnText = document.getElementById('btnText');
            const alertArea = document.getElementById('alertArea');

            // --- REVISI POPUP VALIDASI EMAIL ---
            if (!email || !email.includes('@')) { 
                Swal.fire({
                    icon: 'warning',
                    title: 'Email Tidak Valid',
                    text: 'Mohon isi alamat email yang valid untuk menerima kode OTP!',
                    confirmButtonText: 'Oke, Saya Perbaiki',
                    confirmButtonColor: '#4facfe', // Warna biru tema
                    width: '400px'
                });
                return; 
            }

            btn.disabled = true; const originalText = btnText.innerText; btnText.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; alertArea.innerHTML = '';

            try {
                const formData = new FormData(); formData.append('action', 'send_otp'); formData.append('email', email); formData.append('name', name);
                const response = await fetch('register.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if(data.status === 'success') {
                    // Opsional: Bisa tampilkan popup sukses juga disini
                    Swal.fire({
                        icon: 'success',
                        title: 'OTP Terkirim!',
                        text: 'Silakan cek kotak masuk email Anda.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    
                    alertArea.innerHTML = `<div class="alert alert-success small">${data.message}</div>`;
                    startCountdown(180); 
                } else { 
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message,
                        confirmButtonColor: '#dc3545'
                    });
                    btn.disabled = false; btnText.innerText = originalText; 
                }
            } catch (error) { 
                alertArea.innerHTML = `<div class="alert alert-danger small">Error koneksi server.</div>`; 
                btn.disabled = false; btnText.innerText = originalText; 
            }
        }

        function startCountdown(seconds) {
            const btn = document.getElementById('btnSendOtp'); const btnText = document.getElementById('btnText'); let counter = seconds;
            const interval = setInterval(() => { counter--; btnText.innerText = `${counter}s`; if (counter <= 0) { clearInterval(interval); btn.disabled = false; btnText.innerText = "Kirim Ulang"; } }, 1000);
        }
    </script>
</body>
</html>
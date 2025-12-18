<?php
require_once __DIR__ . '/../src/classes/Auth.php';
$auth = new Auth();

// Redirect jika SUDAH login user asli (bukan guest)
if ($auth->isLoggedIn() && !$auth->isGuest()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = $_POST['login_type'] ?? 'user';
    
    // --- LOGIN GUEST ---
    if ($loginType === 'guest') {
        $result = $auth->guestLogin('Guest');
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    } 
    // --- LOGIN USER BIASA ---
    else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Hapus Guest
        if ($auth->isGuest() || isset($_SESSION['guest_name'])) {
            // Kosongkan memori session saat ini
            $_SESSION = [];

            // Hapus cookie session lama
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }

            // Hancurkan session di server
            session_destroy();
            
            // Mulai session baru
            session_start();
            session_regenerate_id(true);
            
            // Re-inisialisasi Auth dengan session baru
            $auth = new Auth();
        }
        
        // 2. Lakukan Login
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            // Pastikan data session user tersimpan ke database sebelum redirect
            session_write_close(); 
            
            // Baru redirect ke dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Cuaca & Aktivitas</title>
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">

    <div class="login-container">
        <div class="login-wrapper">
            
            <div class="login-form-side">
                <h3><i class="bi bi-box-arrow-in-right"></i> Login</h3>
                
                <?php if ($auth->isGuest()): ?>
                    <div class="alert alert-warning py-2 small shadow-sm border-0">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> 
                        Anda saat ini adalah <b>Guest</b>. Login untuk menyimpan data Anda secara permanen.
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-4">Silakan login untuk melanjutkan.</p>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="login_type" value="user">
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope-fill"></i> Email</label>
                        <input type="email" name="email" class="form-control" placeholder="masukkan email anda" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label"><i class="bi bi-key-fill"></i> Password</label>
                            <a href="forgot_password.php" class="small text-decoration-none">Lupa Password?</a>
                        </div>
                        
                        <div class="input-group">
                            <input type="password" name="password" id="loginPass" class="form-control border-end-0" placeholder="masukkan password anda" required>
                            <span class="input-group-text bg-white border-start-0" style="cursor: pointer;" onclick="togglePassword('loginPass', this)">
                                <i class="bi bi-eye-slash"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        Masuk Sekarang <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>

                <div class="text-center position-relative my-3">
                    <hr>
                    <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 text-muted small">Atau</span>
                </div>

                <?php if (!$auth->isGuest()): ?>
                <form method="POST">
                    <input type="hidden" name="login_type" value="guest">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-incognito me-2"></i> Masuk Tanpa Akun(Guest)
                    </button>
                </form>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <p>Belum punya akun? <a href="register.php" class="fw-bold text-decoration-none">Buat Akun Baru</a></p>
                </div>
            </div>
            
            <div class="login-info-side">
                <h4><i class="bi bi-cloud-sun"></i> Aplikasi Cuaca & Aktivitas</h4>
                
                <div class="feature-item">
                    <i class="bi bi-cloud-check"></i>
                    <div class="feature-text">
                        <h6>Cuaca Real-Time</h6>
                        <p>Pantau cuaca terkini untuk merencanakan aktivitas Anda</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="bi bi-calendar3"></i>
                    <div class="feature-text">
                        <h6>Manajemen Aktivitas</h6>
                        <p>Kelola aktivitas indoor dan outdoor dengan mudah</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="bi bi-bell"></i>
                    <div class="feature-text">
                        <h6>Notifikasi Pintar</h6>
                        <p>Dapatkan pemberitahuan cuaca ekstrem</p>
                    </div>
                </div>

                <div class="info-box mt-4">
                    <strong>💡 Tips:</strong> Gunakan akun Guest untuk melihat cuaca tanpa perlu mendaftar!
                </div>
            </div>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, iconSpan) {
            const input = document.getElementById(inputId);
            const icon = iconSpan.querySelector('i');
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            } else {
                input.type = "password";
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        }
    </script>
</body>
</html>
<?php
require_once __DIR__ . '/../src/classes/Auth.php';

session_start();

if (empty($_SESSION['reset_verified']) || empty($_SESSION['verified_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$auth = new Auth();
$error = '';
$success = '';

$verified_email = $_SESSION['verified_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Semua field harus diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password tidak cocok.';
    } else {
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password in database
            $stmt = $auth->getDb()->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $verified_email]);
            
            // Clear session
            unset($_SESSION['reset_verified']);
            unset($_SESSION['verified_email']);
            unset($_SESSION['reset_email']);
            
            $success = 'Password berhasil direset! Silahkan login dengan password baru Anda.';
            
            echo "
            <script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 2000);
            </script>
            ";
        } catch (PDOException $e) {
            $error = 'Gagal mereset password. Silahkan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Aplikasi Cuaca dan Aktivitas Harian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #87CEEB 0%, #4A90E2 100%);
            padding: 20px;
        }

        .reset-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reset-header .icon {
            font-size: 48px;
            color: #4A90E2;
            margin-bottom: 15px;
        }

        .reset-header h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .reset-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }

        .form-control {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            background: white;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            padding: 8px;
            border-radius: 4px;
            background: #f5f5f5;
            display: none;
        }

        .password-strength.show {
            display: block;
        }

        .strength-bar {
            width: 100%;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }

        .strength-fill.weak {
            width: 33%;
            background: #FF6B6B;
        }

        .strength-fill.medium {
            width: 66%;
            background: #FFA500;
        }

        .strength-fill.strong {
            width: 100%;
            background: #4CAF50;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #87CEEB 0%, #4A90E2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(74, 144, 226, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #4A90E2;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            color: #2E5C8A;
            text-decoration: underline;
        }

        .eye-icon {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            transition: all 0.3s ease;
        }

        .eye-icon:hover {
            color: #4A90E2;
        }

        .password-input-group {
            position: relative;
        }

        .requirements {
            margin-top: 15px;
            padding: 15px;
            background: #f0f7ff;
            border-left: 4px solid #4A90E2;
            border-radius: 4px;
            font-size: 13px;
            color: #555;
        }

        .requirement {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .requirement i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }

        .requirement.met i {
            color: #4CAF50;
        }

        .requirement.unmet i {
            color: #ddd;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <div class="icon">
                    <i class="bi bi-lock-fill"></i>
                </div>
                <h2>Reset Password</h2>
                <p>Buat password baru yang kuat untuk akun Anda</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" id="resetForm">
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="bi bi-lock me-2"></i>Password Baru
                    </label>
                    <div class="password-input-group">
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-control" 
                            placeholder="Minimal 6 karakter"
                            required
                            minlength="6"
                            autocomplete="new-password"
                        >
                        <i class="bi bi-eye eye-icon" onclick="togglePassword('password')"></i>
                    </div>
                    <div class="password-strength" id="strengthIndicator">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span id="strengthText">Kekuatan: Lemah</span>
                    </div>
                    <div class="requirements">
                        <div class="requirement unmet" id="req-length">
                            <i class="bi bi-check-circle-fill"></i>
                            Minimal 6 karakter
                        </div>
                        <div class="requirement unmet" id="req-upper">
                            <i class="bi bi-check-circle-fill"></i>
                            Huruf besar (A-Z)
                        </div>
                        <div class="requirement unmet" id="req-lower">
                            <i class="bi bi-check-circle-fill"></i>
                            Huruf kecil (a-z)
                        </div>
                        <div class="requirement unmet" id="req-number">
                            <i class="bi bi-check-circle-fill"></i>
                            Angka (0-9)
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">
                        <i class="bi bi-lock me-2"></i>Konfirmasi Password
                    </label>
                    <div class="password-input-group">
                        <input 
                            type="password" 
                            name="confirm_password" 
                            id="confirm_password" 
                            class="form-control" 
                            placeholder="Ulangi password"
                            required
                            autocomplete="new-password"
                        >
                        <i class="bi bi-eye eye-icon" onclick="togglePassword('confirm_password')"></i>
                    </div>
                    <div id="matchIndicator" style="margin-top: 8px; font-size: 13px; display: none;">
                        <i class="bi bi-check-circle" style="color: #4CAF50;"></i>
                        <span style="color: #4CAF50;">Password cocok</span>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="bi bi-check-lg me-2"></i>Simpan Password Baru
                </button>
            </form>

            <div class="back-link">
                <a href="login.php">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Login
                </a>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthIndicator = document.getElementById('strengthIndicator');
        const submitBtn = document.getElementById('submitBtn');

        // Password strength checker
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const requirements = {
                length: password.length >= 6,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /[0-9]/.test(password)
            };

            // Update requirements display
            document.getElementById('req-length').classList.toggle('met', requirements.length);
            document.getElementById('req-upper').classList.toggle('met', requirements.upper);
            document.getElementById('req-lower').classList.toggle('met', requirements.lower);
            document.getElementById('req-number').classList.toggle('met', requirements.number);

            if (password.length > 0) {
                strengthIndicator.classList.add('show');
            } else {
                strengthIndicator.classList.remove('show');
            }

            // Calculate strength
            let strength = 0;
            if (requirements.length) strength++;
            if (requirements.upper) strength++;
            if (requirements.lower) strength++;
            if (requirements.number) strength++;

            const fillEl = document.getElementById('strengthFill');
            const textEl = document.getElementById('strengthText');

            if (strength <= 1) {
                fillEl.className = 'strength-fill weak';
                textEl.textContent = 'Kekuatan: Lemah';
            } else if (strength <= 2) {
                fillEl.className = 'strength-fill medium';
                textEl.textContent = 'Kekuatan: Sedang';
            } else if (strength >= 3) {
                fillEl.className = 'strength-fill strong';
                textEl.textContent = 'Kekuatan: Kuat';
            }
        });

        // Check if passwords match
        function checkPasswordMatch() {
            const matchIndicator = document.getElementById('matchIndicator');
            if (passwordInput.value && confirmInput.value) {
                if (passwordInput.value === confirmInput.value) {
                    matchIndicator.style.display = 'block';
                    submitBtn.disabled = false;
                } else {
                    matchIndicator.style.display = 'none';
                    submitBtn.disabled = true;
                }
            } else {
                matchIndicator.style.display = 'none';
                submitBtn.disabled = false;
            }
        }

        confirmInput.addEventListener('input', checkPasswordMatch);

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = event.target;
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.add('bi-eye');
                icon.classList.remove('bi-eye-slash');
            }
        }
    </script>
</body>
</html>

<?php
require_once __DIR__ . '/../src/classes/Auth.php';
require_once __DIR__ . '/../src/classes/EmailVerification.php';

session_start();

if (empty($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$auth = new Auth();
$emailVerify = new EmailVerification();
$error = '';
$success = '';

$reset_email = $_SESSION['reset_email'];
$resend_sent = $_SESSION['resend_sent'] ?? false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'verify') {
        $otp = $_POST['otp'] ?? '';
        
        if (empty($otp)) {
            $error = 'Silahkan masukkan kode OTP.';
        } else {
            // Verify OTP
            if ($emailVerify->verifyOTP($reset_email, $otp, 'reset_password')) {
                // Mark as verified in session
                $_SESSION['reset_verified'] = true;
                $_SESSION['verified_email'] = $reset_email;
                unset($_SESSION['resend_sent']);
                
                // Redirect to password reset page
                header('Location: reset_password.php');
                exit;
            } else {
                $error = 'Kode OTP salah atau sudah kadaluarsa.';
            }
        }
    } elseif ($_POST['action'] === 'resend') {
        // Resend OTP
        $otp = $emailVerify->generateOTP();
        
        if ($emailVerify->sendPasswordResetEmail($reset_email, 'User', $otp)) {
            $emailVerify->storeOTP($reset_email, $otp, 'reset_password');
            $success = 'Kode OTP baru telah dikirim ke email Anda.';
            $_SESSION['resend_sent'] = true;
        } else {
            $error = 'Gagal mengirim ulang kode OTP.';
        }
    } elseif ($_POST['action'] === 'cancel') {
        unset($_SESSION['reset_email']);
        unset($_SESSION['resend_sent']);
        header('Location: forgot_password.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Reset Password - Aplikasi Cuaca dan Aktivitas Harian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #87CEEB 0%, #4A90E2 100%);
            padding: 20px;
        }

        .verify-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }

        .verify-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .verify-header .icon {
            font-size: 48px;
            color: #4A90E2;
            margin-bottom: 15px;
        }

        .verify-header h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .verify-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .verify-email {
            background: #f0f7ff;
            padding: 10px 15px;
            border-radius: 6px;
            color: #4A90E2;
            font-weight: 500;
            margin-bottom: 25px;
            text-align: center;
            font-size: 13px;
            word-break: break-all;
        }

        .single-otp-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            letter-spacing: 4px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .single-otp-input:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .verify-btn {
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
            margin-bottom: 10px;
        }

        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(74, 144, 226, 0.4);
        }

        .cancel-btn {
            width: 100%;
            padding: 12px;
            background: #f0f0f0;
            color: #666;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cancel-btn:hover {
            background: #e0e0e0;
        }

        .resend-section {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }

        .resend-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .resend-btn {
            background: none;
            border: none;
            color: #4A90E2;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .resend-btn:hover {
            color: #2E5C8A;
            text-decoration: underline;
        }

        .resend-btn:disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .countdown {
            color: #999;
            font-size: 13px;
            margin-top: 8px;
        }

        .timer {
            color: #4A90E2;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-header">
                <div class="icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h2>Verifikasi Kode OTP</h2>
                <p>Masukkan kode OTP yang telah dikirim ke email Anda</p>
            </div>

            <div class="verify-email">
                <i class="bi bi-envelope"></i> <?= htmlspecialchars($reset_email) ?>
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

            <form method="POST">
                <input type="hidden" name="action" value="verify">
                
                <input 
                    type="text" 
                    name="otp" 
                    class="single-otp-input" 
                    placeholder="000000" 
                    maxlength="6" 
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    required
                    autocomplete="off"
                >

                <button type="submit" class="verify-btn">
                    <i class="bi bi-check-lg me-2"></i>Verifikasi Kode
                </button>

                <button type="button" class="cancel-btn" onclick="confirmCancel()">
                    <i class="bi bi-x-lg me-2"></i>Batal
                </button>
            </form>

            <div class="resend-section">
                <p class="resend-text">Belum menerima kode?</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="resend-btn" id="resendBtn">
                        Kirim Ulang Kode
                    </button>
                </form>
                <div class="countdown" id="countdownContainer" style="display: none;">
                    Kirim ulang dalam <span class="timer" id="countdown">60</span> detik
                </div>
            </div>
        </div>
    </div>

    <script>
        const otpInput = document.querySelector('input[name="otp"]');
        
        otpInput.addEventListener('input', function(e) {
            if (this.value.length === 6 && /^\d{6}$/.test(this.value)) {
                setTimeout(() => {
                    this.closest('form').submit();
                }, 300);
            }
        });

        function confirmCancel() {
            if (confirm('Yakin ingin membatalkan reset password?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="cancel">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function startCountdown() {
            let remaining = 60;
            const resendBtn = document.getElementById('resendBtn');
            const countdownContainer = document.getElementById('countdownContainer');
            const countdown = document.getElementById('countdown');

            resendBtn.disabled = true;
            countdownContainer.style.display = 'block';

            const timer = setInterval(() => {
                remaining--;
                countdown.textContent = remaining;

                if (remaining === 0) {
                    clearInterval(timer);
                    resendBtn.disabled = false;
                    countdownContainer.style.display = 'none';
                }
            }, 1000);
        }

        <?php if ($resend_sent): ?>
        startCountdown();
        <?php endif; ?>
    </script>
</body>
</html>

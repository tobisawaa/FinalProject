<?php
require_once __DIR__ . '/../src/classes/Auth.php';
require_once __DIR__ . '/../src/classes/EmailVerification.php';

session_start();

$auth = new Auth();
$emailVerify = new EmailVerification();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Silahkan masukkan email Anda.';
    } else {
        // Check if email exists
        $checkEmail = $auth->getDb()->query("SELECT id, name FROM users WHERE email = ?", [$email]);

        if ($checkEmail->rowCount() === 0) {
            $error = 'Email tidak terdaftar.';
        } else {
            $user = $checkEmail->fetch(PDO::FETCH_ASSOC);
            
            // Generate OTP
            $otp = $emailVerify->generateOTP();
            
            // Store OTP in database (don't wait for email)
            $emailVerify->storeOTP($email, $otp, 'reset_password');
            
            // Try to send email (optional)
            $emailVerify->sendPasswordResetEmail($email, $user['name'], $otp);
            
            // Store email in session
            $_SESSION['reset_email'] = $email;
            
            // Redirect to OTP verification
            header('Location: verify_reset_password.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Aplikasi Cuaca dan Aktivitas Harian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .forgot-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #87CEEB 0%, #4A90E2 100%);
            padding: 20px;
        }

        .forgot-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .forgot-header .icon {
            font-size: 48px;
            color: #4A90E2;
            margin-bottom: 15px;
        }

        .forgot-header h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .forgot-header p {
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

        .info-box {
            background: #f0f7ff;
            border-left: 4px solid #4A90E2;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
            line-height: 1.6;
        }

        .info-box i {
            color: #4A90E2;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <div class="icon">
                    <i class="bi bi-key"></i>
                </div>
                <h2>Lupa Password</h2>
                <p>Masukkan email Anda untuk mereset password</p>
            </div>

            <div class="info-box">
                <i class="bi bi-info-circle"></i>
                Kami akan mengirimkan kode OTP ke email Anda untuk memverifikasi identitas Anda.
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class="bi bi-envelope me-2"></i>Alamat Email
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-control" 
                        placeholder="nama@example.com"
                        required
                        autocomplete="email"
                    >
                </div>

                <button type="submit" class="submit-btn">
                    <i class="bi bi-send me-2"></i>Kirim Kode Verifikasi
                </button>
            </form>

            <div class="back-link">
                <a href="login.php">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>

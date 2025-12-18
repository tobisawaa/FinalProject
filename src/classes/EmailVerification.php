<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailVerification {
    
    // Konfigurasi SMTP
    private $sender_email = 'webmin.cuaca@gmail.com'; 
    private $sender_password = 'ospyhjspwpngmfbp'; // Password App
    // ------------------------

    public function generateOTP() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function storeOTP($email, $otp, $type) {
        try {
             // Koneksi Database Manual
            $db = new PDO("mysql:host=localhost;dbname=weather_activity_app", "admin", "admin123*<3"); 
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Hapus OTP lama
            $stmt = $db->prepare("DELETE FROM email_verifications WHERE email = ? AND type = ?");
            $stmt->execute([$email, $type]);
            
            // Simpan OTP baru
            $stmt = $db->prepare("INSERT INTO email_verifications (email, otp, type, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 3 MINUTE))");
            $stmt->execute([$email, $otp, $type]);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function verifyOTP($email, $otp, $type) {
        try {
            $db = new PDO("mysql:host=localhost;dbname=weather_activity_app", "admin", "admin123*<3");
            $stmt = $db->prepare("SELECT * FROM email_verifications WHERE email = ? AND otp = ? AND type = ? AND expires_at > NOW()");
            $stmt->execute([$email, $otp, $type]);
            
            if ($stmt->rowCount() > 0) {
                // Hapus OTP setelah dipakai agar tidak bisa dipakai 2x
                $del = $db->prepare("DELETE FROM email_verifications WHERE email = ?");
                $del->execute([$email]);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function sendVerificationEmail($email, $name, $otp) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();                                            
            $mail->Host       = 'smtp.gmail.com';                     
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = $this->sender_email;                     
            $mail->Password   = $this->sender_password;                               
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
            $mail->Port       = 465;                                    

            $mail->setFrom($this->sender_email, 'Admin Aplikasi Cuaca');
            $mail->addAddress($email, $name);     

            $mail->isHTML(true);                                  
            $mail->Subject = 'Kode OTP Registrasi';
            $mail->Body    = "
                <div style='font-family: Arial; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h2 style='color: #4A90E2;'>Verifikasi Email</h2>
                    <p>Halo <strong>$name</strong>,</p>
                    <p>Gunakan kode OTP berikut untuk menyelesaikan pendaftaran:</p>
                    <h1 style='letter-spacing: 5px; color: #333;'>$otp</h1>
                    <p style='color: red; font-size: 12px;'>Kode kadaluarsa dalam 3 menit.</p>
                </div>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function sendPasswordResetEmail($email, $name, $otp) {
        $subject = "Reset Password - WeatherApp";
        
        // Isi Pesan HTML
        $body = "
        <div style='font-family: Arial; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
            <h3 style='color: #4A90E2;'>Permintaan Reset Password</h3>
            <p>Halo <strong>$name</strong>,</p>
            <p>Seseorang telah meminta reset password untuk akun ini.</p>
            <p>Gunakan kode OTP berikut:</p>
            <h2 style='letter-spacing: 5px; color: #333;'>$otp</h2>
            <p>Jangan berikan kode ini kepada siapapun.</p>
        </div>
        ";

        // Menggunakan PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();                                            
            $mail->Host       = 'smtp.gmail.com';                     
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = $this->sender_email;                     
            $mail->Password   = $this->sender_password;                               
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
            $mail->Port       = 465;

            $mail->setFrom($this->sender_email, 'Admin Aplikasi Cuaca');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
} 
?>
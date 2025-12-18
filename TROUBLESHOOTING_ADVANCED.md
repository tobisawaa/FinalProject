# 🔧 Troubleshooting & Advanced Usage

## 📧 Email Configuration

### Problem: Email tidak terkirim

**Solusi:**

1. **Check PHP Configuration**
```bash
php -i | grep mail
```

2. **Test Email Function**
```php
// Create test file: tools/test_email.php
<?php
$to = "your-email@example.com";
$subject = "Test Email";
$message = "This is a test email";
$headers = "From: noreply@app.com";

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email";
}
?>
```

3. **Setup SMTP (Laragon)**
   - Open: `C:\laragon\etc\php\php.ini`
   - Find section `[mail function]`
   - Configure SMTP settings
   - Restart Laragon Apache

4. **Using Mailer Library (Alternative)**
```php
// Use PHPMailer or SwiftMailer for better reliability
composer require phpmailer/phpmailer
```

---

## 🔐 OTP Configuration

### Change OTP Validity Duration

```php
// File: src/classes/EmailVerification.php
// Find: storeOTP() method
// Current: 900 seconds (15 minutes)

// Change to:
$expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
$expires_at = date('Y-m-d H:i:s', time() + 600);  // 10 minutes
$expires_at = date('Y-m-d H:i:s', time() + 60);   // 1 minute
```

### Change OTP Length

```php
// File: src/classes/EmailVerification.php
// Find: generateOTP() method
// Current: 6 digits

// For 8 digits:
return str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);

// For 4 digits (not recommended):
return str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
```

### Add Rate Limiting

```php
// Add to EmailVerification.php
public function checkAttempts($email, $type = 'register') {
    $attempts = $this->db->fetchOne(
        "SELECT COUNT(*) as count FROM email_verifications 
         WHERE email = ? AND type = ? 
         AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)",
        [$email, $type]
    );
    
    if ($attempts['count'] >= 5) {
        return false; // Too many attempts
    }
    return true;
}
```

---

## 💻 Database Operations

### View All Pending OTPs

```sql
SELECT id, email, type, expires_at, created_at 
FROM email_verifications 
WHERE expires_at > NOW()
ORDER BY created_at DESC;
```

### View Expired OTPs

```sql
SELECT id, email, type, expires_at 
FROM email_verifications 
WHERE expires_at < NOW();
```

### Delete Expired OTPs

```sql
DELETE FROM email_verifications 
WHERE expires_at < NOW();
```

### View Verified Users

```sql
SELECT id, name, email, is_verified, created_at 
FROM users 
WHERE is_verified = 1;
```

### View Unverified Users

```sql
SELECT id, name, email, is_verified, created_at 
FROM users 
WHERE is_verified = 0;
```

### Manual User Verification

```sql
UPDATE users SET is_verified = 1 WHERE id = 5;
```

---

## 🧪 Testing

### Test Registration Flow

```php
// Create: tools/test_registration.php
<?php
session_start();
require_once __DIR__ . '/../src/classes/EmailVerification.php';

$email = new EmailVerification();

// Generate OTP
$otp = $email->generateOTP();
echo "Generated OTP: " . $otp . "\n";

// Store OTP
$email->storeOTP("test@example.com", $otp, 'register');
echo "OTP stored successfully\n";

// Try to verify
if ($email->verifyOTP("test@example.com", $otp, 'register')) {
    echo "OTP verified successfully\n";
} else {
    echo "OTP verification failed\n";
}
?>
```

### Test Email Sending

```php
// Create: tools/test_email_sending.php
<?php
require_once __DIR__ . '/../src/classes/EmailVerification.php';

$email = new EmailVerification();

// Test registration email
if ($email->sendVerificationEmail(
    "test@example.com",
    "John Doe",
    "123456"
)) {
    echo "Registration email sent successfully\n";
} else {
    echo "Failed to send registration email\n";
}

// Test password reset email
if ($email->sendPasswordResetEmail(
    "test@example.com",
    "John Doe",
    "654321"
)) {
    echo "Password reset email sent successfully\n";
} else {
    echo "Failed to send password reset email\n";
}
?>
```

---

## 🐛 Common Issues

### Issue 1: "Email tidak ditemukan"
**Cause**: Email doesn't exist in database  
**Solution**: Make sure user registered first or email is correct

### Issue 2: "OTP salah atau sudah kadaluarsa"
**Cause**: 
- OTP expired (15 minutes passed)
- Wrong OTP code
- Used another verify attempt before correct one

**Solution**:
- Check email again
- Click "Kirim Ulang Kode" to get new OTP
- Verify quickly (within 15 minutes)

### Issue 3: Email tidak diterima
**Cause**: 
- SMTP not configured
- Email marked as spam
- Server firewall blocking

**Solution**:
- Check SMTP configuration
- Check spam/junk folder
- Check server firewall rules
- Use alternative email service (Mailtrap, SendGrid)

### Issue 4: Password reset not working
**Cause**: Session expired or cookies disabled  
**Solution**: Clear browser cache, enable cookies, try again

### Issue 5: Can't login after verification
**Cause**: is_verified not set to 1  
**Solution**: Manually update in database:
```sql
UPDATE users SET is_verified = 1 WHERE email = 'your@email.com';
```

---

## 🔍 Debugging

### Enable Debug Logging

```php
// Add to EmailVerification.php methods
error_log("DEBUG: OTP generated: " . $otp);
error_log("DEBUG: Email stored: " . $email);
error_log("DEBUG: OTP verified for: " . $email);
```

### Check Session Data

```php
// Add to verify_email.php
echo "<pre>";
echo "Session data:\n";
var_dump($_SESSION);
echo "</pre>";
```

### Check Database Entries

```php
// Create: tools/check_otp.php
<?php
require_once __DIR__ . '/../src/classes/Database.php';
$db = Database::getInstance();

$otps = $db->fetchAll("SELECT * FROM email_verifications ORDER BY created_at DESC LIMIT 10");

foreach ($otps as $otp) {
    echo "Email: " . $otp['email'] . "\n";
    echo "Type: " . $otp['type'] . "\n";
    echo "Expires: " . $otp['expires_at'] . "\n";
    echo "---\n";
}
?>
```

---

## 🔄 Workflow Customization

### Add Custom Email Template

```php
// In EmailVerification.php, modify sendVerificationEmail()

$html = "
    <div style='background: #f0f0f0; padding: 20px;'>
        <h2>Welcome {NAME}!</h2>
        <p>Your verification code is:</p>
        <h1>{OTP}</h1>
        <p>This code expires in 15 minutes.</p>
        <!-- Your custom content -->
    </div>
";
```

### Add Custom Validation

```php
// In register.php before form submission

// Add phone verification
$phone = $_POST['phone'] ?? '';
if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
    $error = 'Phone number format invalid';
}

// Add age check
$age = (int)($_POST['age'] ?? 0);
if ($age < 18) {
    $error = 'Must be at least 18 years old';
}
```

### Add Resend Limit

```php
// In verify_email.php
$resend_count = (int)($_SESSION['resend_count'] ?? 0);

if ($resend_count >= 3) {
    $error = 'Maximum resend attempts reached. Please try again later.';
} else {
    $_SESSION['resend_count']++;
    // Send OTP
}
```

---

## 📊 Monitoring

### Create Admin Dashboard

```php
// Create: public/admin_verification_stats.php
<?php
require_once __DIR__ . '/../src/classes/Auth.php';
require_once __DIR__ . '/../src/classes/Database.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->getRole() !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Total registrations
$total = $db->fetchOne("SELECT COUNT(*) as count FROM users");

// Verified users
$verified = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_verified = 1");

// Pending OTPs
$pending = $db->fetchOne(
    "SELECT COUNT(*) as count FROM email_verifications WHERE expires_at > NOW()"
);

// Display stats
echo "Total users: " . $total['count'];
echo "Verified: " . $verified['count'];
echo "Pending: " . $pending['count'];
?>
```

---

## 🚀 Performance Optimization

### Add Database Indexes

```sql
-- Improve query performance
CREATE INDEX idx_users_verified ON users(is_verified);
CREATE INDEX idx_otp_expires ON email_verifications(expires_at);
```

### Cleanup Expired OTPs (Cron Job)

```php
// Create: tools/cleanup_expired_otps.php
<?php
require_once __DIR__ . '/../src/classes/Database.php';

$db = Database::getInstance();
$db->execute(
    "DELETE FROM email_verifications WHERE expires_at < NOW()"
);

echo "Cleanup completed at " . date('Y-m-d H:i:s');
?>

// Run every hour via cron:
// 0 * * * * php /path/to/tools/cleanup_expired_otps.php
```

---

## 📱 Mobile Optimization

### Test Responsiveness
- Test on iPhone 12 (390px)
- Test on iPhone 13 Pro Max (430px)
- Test on Samsung Galaxy S21 (360px)
- Test on iPad (768px)

### Mobile Issues & Fixes

**Issue**: Keyboard covers OTP input  
**Solution**: Add to CSS:
```css
body {
    overflow-y: scroll;
    padding-bottom: 50vh;
}
```

**Issue**: Font too small on mobile  
**Solution**: Bootstrap already handles this, check viewport meta tag

**Issue**: Buttons too small to tap  
**Solution**: Ensure min 44px height (already done in CSS)

---

## 🔒 Security Hardening

### Add CSRF Protection

```php
// In each form
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In form:
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Verify:
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token invalid');
}
```

### Add Input Validation

```php
// Sanitize inputs
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$name = htmlspecialchars($_POST['name']);
$password = $_POST['password']; // Don't htmlspecialchars passwords!
```

### Add Rate Limiting

```php
// Check failed attempts
$ip = $_SERVER['REMOTE_ADDR'];
$attempts = $redis->incr("login_attempts:" . $ip);

if ($attempts > 5) {
    die('Too many attempts. Please try again later.');
}

$redis->expire("login_attempts:" . $ip, 3600); // 1 hour
```

---

## 📞 Support & Resources

- **PHP Mail**: https://www.php.net/manual/en/function.mail.php
- **Password Hashing**: https://www.php.net/manual/en/function.password-hash.php
- **Bootstrap**: https://getbootstrap.com/docs/5.0/
- **Bootstrap Icons**: https://icons.getbootstrap.com/

---

**Last Updated**: 2024  
**Version**: 1.0  
**Status**: Production Ready

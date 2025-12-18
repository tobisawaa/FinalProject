# 🔐 Email Verification & Password Reset System

## 📋 Overview

Sistem keamanan modern untuk mengelola registrasi dan reset password dengan OTP (One-Time Password) verification. Mencegah spam registrasi dan memberikan reset password yang aman.

---

## 🔄 Flow Diagrams

### Registrasi dengan Email Verification

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  USER: Akses public/register.php                           │
│                                                             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │  Isi Form Registrasi:        │
        │  - Nama                      │
        │  - Email                     │
        │  - Password                  │
        │  - Konfirmasi Password       │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │  Validasi Input & Email      │
        │  (Server-side)               │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │  Generate OTP 6-digit        │
        │  - Hashing dengan bcrypt     │
        │  - Simpan di tabel           │
        │    email_verifications       │
        │  - Set expiration 15 menit   │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │  Kirim Email Verifikasi      │
        │  - HTML template             │
        │  - Sky blue branding         │
        │  - Berisi OTP code           │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │  Store data di session:      │
        │  $_SESSION['temp_register']  │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │  Redirect ke               │
        │  public/verify_email.php     │
        └──────────────┬───────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  USER: Cek email dan masukkan OTP                         │
│                                                             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │  Input OTP (6-digit)         │
        │  - Numeric only              │
        │  - Auto-submit saat 6 digit  │
        │    atau click "Verifikasi"   │
        └──────────────┬───────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │  Validasi OTP:               │
        │  - Cek di database           │
        │  - Hash matching             │
        │  - Check expiration          │
        └──────────────┬───────────────┘
                       │
        ┌──────────────┴──────────────┐
        │                             │
        ▼ (Benar)                     ▼ (Salah)
┌──────────────────────┐     ┌──────────────────────┐
│ Buat user account:   │     │ Show error message   │
│ - INSERT ke users    │     │ - "OTP salah atau"   │
│ - is_verified = 1    │     │   "sudah kadaluarsa" │
│ - Hash password      │     │ - Provide resend btn │
└──────────┬───────────┘     └──────────────────────┘
           │                          ▲
           │                          │
           ▼                    (Resend OTP)
┌──────────────────────────┐         │
│ Clear session:           │─────────┘
│ - unset temp_register    │
│ - unset resend_sent      │
└──────────┬───────────────┘
           │
           ▼
┌──────────────────────────────┐
│  Show Success Message        │
│  "Email berhasil             │
│   diverifikasi!"             │
└──────────┬───────────────────┘
           │
           ▼
    ┌─────────────────────┐
    │  Redirect ke login  │
    │  (2 detik)          │
    └─────────────────────┘
```

---

### Lupa Password dengan OTP

```
┌──────────────────────────────────────────────────────────┐
│                                                          │
│  USER: Di halaman login.php, klik "Lupa Password?"      │
│                                                          │
└────────────────────┬─────────────────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │  Redirect ke               │
        │  public/forgot_password.php│
        └────────────┬───────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │  Input email terdaftar     │
        │  - Validasi format email   │
        │  - Cek di database         │
        └────────────┬───────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
        ▼ (Email found)           ▼ (Not found)
┌──────────────────────┐   ┌──────────────────┐
│ Generate OTP:        │   │ Show error:      │
│ - 6-digit random     │   │ "Email tidak"    │
│ - Hash dengan bcrypt │   │  "terdaftar"     │
│ - Store di tabel     │   └──────────────────┘
│   email_verifications│
│ - Expiration 15 min  │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────────┐
│  Kirim Email Reset:      │
│  - HTML template         │
│  - Sky blue branding     │
│  - Berisi OTP code       │
└──────────┬───────────────┘
           │
           ▼
┌──────────────────────────┐
│  Store email di session: │
│  $_SESSION['reset_email']│
└──────────┬───────────────┘
           │
           ▼
┌──────────────────────────────┐
│  Redirect ke                 │
│  public/verify_reset_password│
└──────────┬───────────────────┘
           │
           ▼
┌─────────────────────────────────────────────┐
│                                             │
│  USER: Cek email dan input OTP              │
│                                             │
└────────────┬────────────────────────────────┘
             │
             ▼
┌────────────────────────────┐
│  Input OTP (6-digit)       │
│  - Auto-submit atau click  │
│    "Verifikasi Kode"       │
└────────────┬───────────────┘
             │
             ▼
┌────────────────────────────┐
│  Validasi OTP:             │
│  - Cek database            │
│  - Hash matching           │
│  - Check expiration        │
└────────────┬───────────────┘
             │
    ┌────────┴────────┐
    │                 │
    ▼ (Benar)         ▼ (Salah)
┌──────────────────┐ ┌──────────────┐
│ Mark verified:   │ │ Show error & │
│ - Session flag   │ │ resend btn   │
│ - Store email    │ └──────────────┘
└────────┬─────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Redirect ke                     │
│ public/reset_password.php       │
└────────┬────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│                                          │
│  USER: Input password baru dan konfirmasi│
│                                          │
└────────┬─────────────────────────────────┘
         │
         ▼
┌────────────────────────────┐
│  Input Password Baru:      │
│  - Show strength indicator │
│  - Requirements checklist: │
│    ✓ Minimal 6 karakter    │
│    ✓ Huruf besar (A-Z)     │
│    ✓ Huruf kecil (a-z)     │
│    ✓ Angka (0-9)           │
│  - Konfirmasi password     │
└────────┬───────────────────┘
         │
         ▼
┌────────────────────────────┐
│  Validasi:                 │
│  - Panjang minimal 6 char  │
│  - Password match          │
└────────┬───────────────────┘
         │
         ▼
┌────────────────────────────┐
│  Update password di users: │
│  - Hash dengan bcrypt      │
│  - UPDATE WHERE email      │
└────────┬───────────────────┘
         │
         ▼
┌────────────────────────────┐
│  Clear session:            │
│  - unset reset_verified    │
│  - unset verified_email    │
│  - unset reset_email       │
└────────┬───────────────────┘
         │
         ▼
┌──────────────────────────┐
│  Show Success Message:   │
│  "Password berhasil      │
│   direset!"              │
└────────┬─────────────────┘
         │
         ▼
    ┌─────────────────────┐
    │  Redirect ke login  │
    │  (2 detik)          │
    └─────────────────────┘
```

---

## 📊 Database Schema

### Tabel `users` (Updated)
```sql
ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER password;

/*
id              INT PRIMARY KEY AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
email           VARCHAR(255) UNIQUE NOT NULL
password        VARCHAR(255) NOT NULL
is_verified     TINYINT(1) DEFAULT 0          ← NEW COLUMN
role            VARCHAR(50) DEFAULT 'user'
created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE
*/
```

### Tabel `email_verifications` (New)
```sql
CREATE TABLE email_verifications (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    email       VARCHAR(255) NOT NULL,
    otp         VARCHAR(255) NOT NULL,          -- Hashed OTP
    type        VARCHAR(50) DEFAULT 'register', -- 'register' atau 'reset_password'
    expires_at  TIMESTAMP NULL,                 -- Expiration time
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_type (type),
    INDEX idx_expires_at (expires_at)
);
```

---

## 🗂️ File Structure

```
Final Projek/
├── public/
│   ├── register.php                 ← Updated: OTP verification flow
│   ├── login.php                    ← Already has "Lupa Password?" link
│   ├── verify_email.php             ← NEW: OTP input for registration
│   ├── forgot_password.php          ← NEW: Email input for password reset
│   ├── verify_reset_password.php    ← NEW: OTP input for password reset
│   ├── reset_password.php           ← NEW: New password input
│   ├── assets/
│   │   └── css/
│   │       ├── login.css            ← Sky blue theme (existing)
│   │       └── style.css
│   └── ...
│
├── src/
│   └── classes/
│       ├── EmailVerification.php    ← NEW: OTP & email handling
│       ├── Auth.php                 ← Updated: is_verified check
│       └── ...
│
├── tools/
│   ├── migrate_email_verification.php ← NEW: Database migration
│   └── ...
│
├── docs/
│   └── EMAIL_VERIFICATION.md        ← NEW: Complete documentation
│
└── IMPLEMENTATION_SUMMARY.txt       ← NEW: Implementation checklist
```

---

## 🔐 Security Features

### OTP System
```
✅ 6-digit numeric random generation
✅ Hashed dengan PASSWORD_DEFAULT (bcrypt)
✅ 15-minute expiration
✅ One-time use only (deleted after verification)
✅ Type-based (register vs reset_password)
✅ Database storage dengan timestamp
```

### Password Security
```
✅ Minimum 6 characters requirement
✅ Strength indicator (weak/medium/strong)
✅ Requirements checklist:
   - Uppercase letters (A-Z)
   - Lowercase letters (a-z)
   - Numbers (0-9)
✅ Hashed dengan PASSWORD_DEFAULT sebelum disimpan
✅ Match validation sebelum save
```

### Session Management
```
✅ Temporary data di $_SESSION
✅ Automatic cleanup setelah proses
✅ Email validation
✅ Error handling
✅ Input sanitization (htmlspecialchars)
```

---

## 🎨 UI/UX Features

### Design Consistency
- **Color Scheme**: Sky blue gradient (#87CEEB → #4A90E2)
- **Framework**: Bootstrap 5.3.0
- **Icons**: Bootstrap Icons
- **Responsive**: Mobile-first design
- **Animations**: Smooth transitions

### User Experience
```
✅ Auto-submit OTP when 6 digits filled
✅ Resend countdown timer (60 seconds)
✅ Clear error messages
✅ Success feedback
✅ Password strength indicator
✅ Show/hide password toggle
✅ Requirements checklist
✅ Consistent navigation
✅ Back/cancel options
✅ Loading indicators (optional)
```

---

## 🚀 Implementation Checklist

- [x] Create `EmailVerification` class
- [x] Create database migration
- [x] Update `register.php` with OTP flow
- [x] Create `verify_email.php`
- [x] Create `forgot_password.php`
- [x] Create `verify_reset_password.php`
- [x] Create `reset_password.php`
- [x] Update `Auth.php` with verification check
- [x] Add `getDb()` method to Auth
- [x] Run database migration
- [x] Validate all PHP files
- [x] Test registration flow
- [x] Test password reset flow
- [x] Create documentation
- [x] Create implementation summary

---

## 📝 Usage Examples

### Test Registration
```bash
1. Go to: http://localhost/Final%20Projek/public/register.php
2. Fill form with test data
3. Check email for OTP code
4. Enter OTP in verify_email.php
5. Account created and verified ✅
```

### Test Password Reset
```bash
1. Go to: http://localhost/Final%20Projek/public/login.php
2. Click "Lupa Password?" link
3. Enter registered email
4. Check email for OTP code
5. Enter OTP and new password
6. Password updated ✅
```

---

## 🔧 Configuration

### OTP Expiration Time
- **File**: `src/classes/EmailVerification.php`
- **Line**: ~60
- **Default**: 15 minutes (900 seconds)
- **Change**: Edit `time() + 900` to desired seconds

### OTP Length
- **File**: `src/classes/EmailVerification.php`
- **Line**: ~15
- **Default**: 6 digits
- **Change**: Edit `mt_rand(0, 999999)` and `str_pad(..., 6, ...)`

### Email Sender
- **File**: `src/classes/EmailVerification.php`
- **Lines**: ~25, ~75
- **From**: `noreply@app.com` (or your domain)
- **Change**: Edit `$from` variable

---

## 🐛 Debugging

### Enable Debug Mode
```php
// Add to any verification file to see OTP
error_log("Generated OTP: " . $otp);
error_log("Stored hash: " . password_hash($otp, PASSWORD_DEFAULT));
```

### Check Database
```sql
SELECT * FROM email_verifications;
SELECT id, email, is_verified FROM users;
```

### Test Email Sending
```bash
php -r "mail('test@email.com', 'Test', 'Works!');"
```

---

## ✨ Features Overview

| Feature | Status | Notes |
|---------|--------|-------|
| Email Verification | ✅ | OTP based |
| Password Reset | ✅ | OTP based |
| OTP Generation | ✅ | 6-digit |
| Email Template | ✅ | HTML format |
| Session Management | ✅ | Auto cleanup |
| Password Strength | ✅ | Real-time indicator |
| Resend OTP | ✅ | With countdown |
| Auto-submit OTP | ✅ | When 6 digits |
| Mobile Responsive | ✅ | Bootstrap 5 |
| Sky Blue Theme | ✅ | Consistent design |

---

## 📞 Support

- **Email Verification**: `public/verify_email.php`
- **Forgot Password**: `public/forgot_password.php`
- **Password Reset**: `public/reset_password.php`
- **Documentation**: `docs/EMAIL_VERIFICATION.md`
- **Implementation**: `IMPLEMENTATION_SUMMARY.txt`

---

**Status**: ✅ Production Ready  
**Version**: 1.0  
**Last Updated**: 2024  

🎉 **Email Verification & Password Reset System is ready to use!**

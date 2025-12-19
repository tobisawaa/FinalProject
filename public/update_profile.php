<?php
require_once __DIR__ . '/../../src/classes/Auth.php';
require_once __DIR__ . '/../../src/classes/Database.php';

$auth = new Auth();
header('Content-Type: application/json');

// Cek Login
if (!$auth->isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();
$userId = $user['id'];
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validasi Dasar
    if (empty($name) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama dan Email wajib diisi.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
        exit;
    }

    try {
        // Cek apakah email sudah dipakai orang lain
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email sudah digunakan pengguna lain.']);
            exit;
        }

        // Siapkan Query Update
        $query = "UPDATE users SET name = ?, email = ?";
        $params = [$name, $email];

        // Jika user ingin ganti password
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                echo json_encode(['status' => 'error', 'message' => 'Password minimal 6 karakter.']);
                exit;
            }
            if ($newPassword !== $confirmPassword) {
                echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password tidak cocok.']);
                exit;
            }
            $query .= ", password = ?";
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $query .= " WHERE id = ?";
        $params[] = $userId;

        $stmt = $db->prepare($query);
        if ($stmt->execute($params)) {
            echo json_encode(['status' => 'success', 'message' => 'Profil berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan perubahan.']);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan server.']);
    }
}
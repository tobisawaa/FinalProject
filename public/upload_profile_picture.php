<?php
require_once __DIR__ . '/../src/classes/Auth.php';
session_start();

$auth = new Auth();
if (!$auth->isLoggedIn() || $auth->isGuest()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image'])) {
    $data = $_POST['image']; // Base64 string
    
    // Validasi data gambar
    if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
        $data = substr($data, strpos($data, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif

        if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
            echo json_encode(['status' => 'error', 'message' => 'Format gambar tidak didukung']);
            exit;
        }

        $data = base64_decode($data);
        if ($data === false) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal decode gambar']);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data gambar tidak valid']);
        exit;
    }

    // Buat nama file unik
    $fileName = 'profile_' . $user['id'] . '_' . time() . '.' . $type;
    $uploadDir = __DIR__ . '/assets/img/uploads/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Hapus foto lama jika ada (optional, untuk hemat storage)
    // if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/' . $user['profile_picture'])) {
    //     unlink(__DIR__ . '/' . $user['profile_picture']);
    // }

    if (file_put_contents($uploadDir . $fileName, $data)) {
        $dbPath = 'assets/img/uploads/' . $fileName;
        $auth->getDb()->query("UPDATE users SET profile_picture = ? WHERE id = ?", [$dbPath, $user['id']]);
        
        echo json_encode(['status' => 'success', 'url' => $dbPath]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada data gambar']);
}
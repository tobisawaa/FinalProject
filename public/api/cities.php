<?php
// Pastikan path ini sesuai dengan struktur folder Anda
require_once __DIR__ . '/../src/classes/Database.php';

header('Content-Type: application/json');

try {
    // Koneksi ke Database
    $db = Database::getInstance()->getConnection();
    
    // Ambil data dari tabel cities
    $query = "SELECT name, province, lat, lon FROM cities ORDER BY name ASC";
    
    // Fitur pencarian
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $stmt = $db->prepare("SELECT name, province, lat, lon FROM cities WHERE name LIKE ? OR province LIKE ? ORDER BY name ASC");
        $stmt->execute(["%$search%", "%$search%"]);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->query($query);
        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Pastikan output lat/lon menjadi float
    foreach ($cities as &$city) {
        $city['lat'] = (float)$city['lat'];
        $city['lon'] = (float)$city['lon'];
    }
    
    echo json_encode($cities);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data kota: ' . $e->getMessage()]);
}
?>

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/classes/ApiClientWeather.php';

$city = $_GET['city'] ?? 'Jakarta';
$type = $_GET['type'] ?? 'current';

$weatherClient = new ApiClientWeather();

if ($type === 'forecast') {
    $data = $weatherClient->getForecast($city);
} else {
    $data = $weatherClient->getCurrentWeather($city);
}

if ($data) {
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch weather data']);
}

<?php
require_once __DIR__ . '/env.php';

return [
    'host' => env('DB_HOST', 'localhost'),
    'dbname' => env('DB_NAME', 'weather_activity_app'),
    'username' => env('DB_USER', 'admin'),
    'password' => env('DB_PASS', 'admin123*<3'),
    'charset' => 'utf8mb4'
];
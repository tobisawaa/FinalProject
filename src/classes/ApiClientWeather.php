<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../../config/env.php';

class ApiClientWeather {
    private $apiKey;
    private $baseUrl;
    private $db;
    private $cacheTTL;
    
    public function __construct() {
        $this->apiKey = env('OPENWEATHER_API_KEY');
        $this->baseUrl = env('OPENWEATHER_BASE_URL');
        $this->db = Database::getInstance();
        $this->cacheTTL = env('CACHE_TTL', 3600); // 1 hour default
    }
    
    public function getCurrentWeather($city) {
        $cacheKey = "weather_current_{$city}";
        
        // Check cache first
        $cached = $this->getCache($cacheKey);
        if ($cached) {
            return json_decode($cached, true);
        }
        
        // Fetch from API
        $url = "{$this->baseUrl}/weather?q={$city}&appid={$this->apiKey}&units=metric&lang=id";
        $response = $this->fetchFromAPI($url);
        
        if ($response) {
            // Save to cache
            $this->setCache($cacheKey, json_encode($response), $this->cacheTTL);
            
            // Log to database
            $this->logWeather($response);
            
            return $response;
        }
        
        return null;
    }
    
    public function getForecast($city, $days = 5) {
        $cacheKey = "weather_forecast_{$city}_{$days}";
        
        $cached = $this->getCache($cacheKey);
        if ($cached) {
            return json_decode($cached, true);
        }
        
        $url = "{$this->baseUrl}/forecast?q={$city}&appid={$this->apiKey}&units=metric&lang=id";
        $response = $this->fetchFromAPI($url);
        
        if ($response) {
            $this->setCache($cacheKey, json_encode($response), $this->cacheTTL);
            return $response;
        }
        
        return null;
    }
    
    private function fetchFromAPI($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    private function getCache($key) {
        $result = $this->db->fetchOne(
            "SELECT cache_data FROM api_cache WHERE cache_key = ? AND expires_at > NOW()",
            [$key]
        );
        
        return $result ? $result['cache_data'] : null;
    }
    
    private function setCache($key, $data, $ttl) {
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        
        // Delete old cache
        $this->db->delete('api_cache', 'cache_key = ?', [$key]);
        
        // Insert new cache
        $this->db->insert('api_cache', [
            'cache_key' => $key,
            'cache_data' => $data,
            'expires_at' => $expiresAt
        ]);
    }
    
    private function logWeather($weatherData) {
        if (!isset($weatherData['main'])) {
            return;
        }
        
        $this->db->insert('weather_logs', [
            'city' => $weatherData['name'] ?? 'Unknown',
            'country_code' => $weatherData['sys']['country'] ?? null,
            'temperature' => $weatherData['main']['temp'] ?? null,
            'feels_like' => $weatherData['main']['feels_like'] ?? null,
            'humidity' => $weatherData['main']['humidity'] ?? null,
            'pressure' => $weatherData['main']['pressure'] ?? null,
            'weather_condition' => $weatherData['weather'][0]['main'] ?? null,
            'weather_description' => $weatherData['weather'][0]['description'] ?? null,
            'wind_speed' => $weatherData['wind']['speed'] ?? null,
            'clouds' => $weatherData['clouds']['all'] ?? null
        ]);
    }
}
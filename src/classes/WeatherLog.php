<?php
require_once __DIR__ . '/Database.php';

class WeatherLog {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($city = null, $limit = 100) {
        $sql = "SELECT * FROM weather_logs WHERE 1=1";
        $params = [];
        
        if ($city) {
            $sql .= " AND city = ?";
            $params[] = $city;
        }
        
        $sql .= " ORDER BY recorded_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM weather_logs WHERE id = ?",
            [$id]
        );
    }
    
    public function getWeeklyTrend($city, $days = 7) {
        $sql = "SELECT 
                    DATE(recorded_at) as date,
                    AVG(temperature) as avg_temp,
                    AVG(humidity) as avg_humidity,
                    AVG(wind_speed) as avg_wind,
                    MAX(temperature) as max_temp,
                    MIN(temperature) as min_temp
                FROM weather_logs
                WHERE city = ?
                AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(recorded_at)
                ORDER BY date";
        
        return $this->db->fetchAll($sql, [$city, $days]);
    }
    
    public function getConditionStats($city, $days = 30) {
        $sql = "SELECT 
                    weather_condition,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM weather_logs WHERE city = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)), 2) as percentage
                FROM weather_logs
                WHERE city = ?
                AND recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY weather_condition
                ORDER BY count DESC";
        
        return $this->db->fetchAll($sql, [$city, $days, $city, $days]);
    }
}
<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/WeatherLog.php';
require_once __DIR__ . '/Activity.php';

class AnalyticsService {
    private $db;
    private $weatherLog;
    private $activity;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->weatherLog = new WeatherLog();
        $this->activity = new Activity();
    }
    
    public function getWeatherSummary($city, $days = 7) {
        $trend = $this->weatherLog->getWeeklyTrend($city, $days);
        
        if (empty($trend)) {
            return null;
        }
        
        $avgTemp = array_sum(array_column($trend, 'avg_temp')) / count($trend);
        $avgHumidity = array_sum(array_column($trend, 'avg_humidity')) / count($trend);
        $maxTemp = max(array_column($trend, 'max_temp'));
        $minTemp = min(array_column($trend, 'min_temp'));
        
        return [
            'avg_temperature' => round($avgTemp, 1),
            'avg_humidity' => round($avgHumidity, 1),
            'max_temperature' => round($maxTemp, 1),
            'min_temperature' => round($minTemp, 1),
            'trend_data' => $trend
        ];
    }
    
    public function getActivityRecommendations($city) {
        require_once __DIR__ . '/ApiClientWeather.php';
        $weatherClient = new ApiClientWeather();
        $weather = $weatherClient->getCurrentWeather($city);
        
        if (!$weather) {
            return ['error' => 'Unable to fetch weather data'];
        }
        
        $recommendations = [];
        $temp = $weather['main']['temp'];
        $condition = $weather['weather'][0]['main'];
        $windSpeed = $weather['wind']['speed'];
        
        // Outdoor recommendations
        if ($condition === 'Clear' && $temp >= 20 && $temp <= 30 && $windSpeed < 8) {
            $recommendations['outdoor'] = [
                'suitable' => true,
                'score' => 9,
                'message' => 'Perfect weather for outdoor activities!',
                'suggestions' => ['Running', 'Cycling', 'Hiking', 'Picnic', 'Beach activities']
            ];
        } elseif ($condition === 'Rain' || $condition === 'Thunderstorm') {
            $recommendations['outdoor'] = [
                'suitable' => false,
                'score' => 2,
                'message' => 'Tidak direkomendasikan dikarenakan cuaca yang buruk.',
                'suggestions' => []
            ];
        } elseif ($temp > 35) {
            $recommendations['outdoor'] = [
                'suitable' => false,
                'score' => 3,
                'message' => 'Cuaca di luar terlalu panas. Jangan lupa bawa air minum jika harus keluar.',
                'suggestions' => ['Swimming', 'Water sports (with caution)']
            ];
        } else {
            $recommendations['outdoor'] = [
                'suitable' => true,
                'score' => 6,
                'message' => 'Kondisi yang baik untuk aktivitas outdoor.',
                'suggestions' => ['Walking', 'Light jogging', 'Outdoor sports']
            ];
        }
        
        // Indoor recommendations
        if (!$recommendations['outdoor']['suitable']) {
            $recommendations['indoor'] = [
                'suitable' => true,
                'score' => 9,
                'message' => 'Waktu yang cocok untuk aktivitas indoor!',
                'suggestions' => ['Gym workout', 'Yoga', 'Swimming (indoor)', 'Rock climbing (indoor)', 'Badminton']
            ];
        } else {
            $recommendations['indoor'] = [
                'suitable' => true,
                'score' => 6,
                'message' => 'Aktivitas indoor selalu tersedia setiap saat.',
                'suggestions' => ['Fitness class', 'Dance', 'Table tennis', 'Bowling']
            ];
        }
        
        $recommendations['current_weather'] = [
            'temperature' => $temp,
            'condition' => $condition,
            'description' => $weather['weather'][0]['description'],
            'humidity' => $weather['main']['humidity'],
            'wind_speed' => $windSpeed
        ];
        
        return $recommendations;
    }
    
    public function getUserActivityStats($userId) {
        $sql = "SELECT 
                    activity_type,
                    status,
                    COUNT(*) as count
                FROM activities
                WHERE user_id = ?
                GROUP BY activity_type, status";
        
        $stats = $this->db->fetchAll($sql, [$userId]);
        
        $summary = [
            'total_activities' => 0,
            'outdoor_count' => 0,
            'indoor_count' => 0,
            'completed_count' => 0,
            'planned_count' => 0,
            'cancelled_count' => 0
        ];
        
        foreach ($stats as $stat) {
            $summary['total_activities'] += $stat['count'];
            
            if ($stat['activity_type'] === 'outdoor') {
                $summary['outdoor_count'] += $stat['count'];
            } else {
                $summary['indoor_count'] += $stat['count'];
            }
            
            $summary[$stat['status'] . '_count'] += $stat['count'];
        }
        
        return $summary;
    }
    
    public function exportToCSV($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            // Write header
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
}
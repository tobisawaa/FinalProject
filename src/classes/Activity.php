<?php
require_once __DIR__ . '/Database.php';

class Activity {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        return $this->db->insert('activities', $data);
    }
    
    public function getAll($userId = null, $filters = []) {
        $sql = "SELECT a.*, u.name as user_name 
                FROM activities a 
                JOIN users u ON a.user_id = u.id 
                WHERE 1=1";
        $params = [];
        
        if ($userId) {
            $sql .= " AND a.user_id = ?";
            $params[] = $userId;
        }
        
        if (!empty($filters['activity_type'])) {
            $sql .= " AND a.activity_type = ?";
            $params[] = $filters['activity_type'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY a.scheduled_date DESC, a.scheduled_time DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM activities WHERE id = ?",
            [$id]
        );
    }
    
   public function update($id, $data) {
    return $this->db->update('activities', $data, "id = :id", ['id' => $id]);
}
    
    public function delete($id) {
        return $this->db->delete('activities', 'id = ?', [$id]);
    }
    
    public function getUpcoming($userId, $days = 7) {
        $sql = "SELECT * FROM activities 
                WHERE user_id = ? 
                AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND status = 'planned'
                ORDER BY scheduled_date, scheduled_time";
        
        return $this->db->fetchAll($sql, [$userId, $days]);
    }
}
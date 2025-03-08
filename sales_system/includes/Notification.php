<?php
/**
 * Notification Management Class
 */
class Notification {
    private static $instance = null;
    private $db;
    private $userId;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->userId = $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get Notification instance (Singleton)
     * @return Notification
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create a new notification
     * @param int $userId User ID
     * @param string $message Notification message
     * @param string $type Notification type (info, warning, success, error)
     * @param string $module Related module
     * @param string $link Optional link
     * @return bool Whether notification was created
     */
    public function create($userId, $message, $type = 'info', $module = null, $link = null) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                INSERT INTO notifications (
                    user_id, message, type, module, link, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, NOW()
                )
            ");
            
            return $stmt->execute([
                $userId,
                $message,
                $type,
                $module,
                $link
            ]);
        } catch (PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's unread notifications
     * @param int $userId User ID
     * @param int $limit Maximum number of notifications to return
     * @return array Notifications
     */
    public function getUnread($userId = null, $limit = 10) {
        $userId = $userId ?? $this->userId;
        
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT id, message, type, module, link, created_at 
                FROM notifications 
                WHERE user_id = ? AND read_at IS NULL 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting unread notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user's notifications
     * @param int $userId User ID
     * @param int $limit Maximum number of notifications to return
     * @param int $offset Offset for pagination
     * @return array Notifications
     */
    public function get($userId = null, $limit = 50, $offset = 0) {
        $userId = $userId ?? $this->userId;
        
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT id, message, type, module, link, created_at, read_at 
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     * @param int $notificationId Notification ID
     * @param int $userId User ID
     * @return bool Whether notification was marked as read
     */
    public function markRead($notificationId, $userId = null) {
        $userId = $userId ?? $this->userId;
        
        try {
            $stmt = $this->db->getConnection()->prepare("
                UPDATE notifications 
                SET read_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read
     * @param int $userId User ID
     * @return bool Whether notifications were marked as read
     */
    public function markAllRead($userId = null) {
        $userId = $userId ?? $this->userId;
        
        try {
            $stmt = $this->db->getConnection()->prepare("
                UPDATE notifications 
                SET read_at = NOW() 
                WHERE user_id = ? AND read_at IS NULL
            ");
            
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete notification
     * @param int $notificationId Notification ID
     * @param int $userId User ID
     * @return bool Whether notification was deleted
     */
    public function delete($notificationId, $userId = null) {
        $userId = $userId ?? $this->userId;
        
        try {
            $stmt = $this->db->getConnection()->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete old notifications
     * @param int $days Number of days to keep
     * @return bool Whether old notifications were deleted
     */
    public function deleteOld($days = 30) {
        try {
            $stmt = $this->db->getConnection()->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) 
                AND read_at IS NOT NULL
            ");
            
            return $stmt->execute([$days]);
        } catch (PDOException $e) {
            error_log("Error deleting old notifications: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread notification count
     * @param int $userId User ID
     * @return int Number of unread notifications
     */
    public function getUnreadCount($userId = null) {
        $userId = $userId ?? $this->userId;
        
        try {
            $stmt = $this->db->getConnection()->prepare("
                SELECT COUNT(*) 
                FROM notifications 
                WHERE user_id = ? AND read_at IS NULL
            ");
            
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting unread notification count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Create system notification for all users
     * @param string $message Notification message
     * @param string $type Notification type
     * @param string $module Related module
     * @param array $userLevels User levels to notify (empty for all)
     * @return bool Whether notifications were created
     */
    public function createSystemNotification($message, $type = 'info', $module = null, $userLevels = []) {
        try {
            $query = "SELECT id FROM users WHERE status = 'active'";
            
            if (!empty($userLevels)) {
                $placeholders = str_repeat('?,', count($userLevels) - 1) . '?';
                $query .= " AND user_level IN ($placeholders)";
            }
            
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute($userLevels);
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($users as $userId) {
                $this->create($userId, $message, $type, $module);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error creating system notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Prevent cloning of the instance (Singleton)
     */
    private function __clone() {}
    
    /**
     * Prevent unserialize of the instance (Singleton)
     */
    private function __wakeup() {}
}
?>

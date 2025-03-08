<?php
/**
 * System Logger class for handling logs and activity tracking
 */
class Logger {
    private static $instance = null;
    private $db;
    private $logPath;
    private $logTypes = ['error', 'info', 'debug', 'warning'];
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->logPath = LOG_PATH;
        
        // Ensure log directory exists
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
    }
    
    /**
     * Get Logger instance (Singleton)
     * @return Logger
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Log system activity
     * @param string $action Action performed
     * @param string $description Description of the action
     * @param string $module Module where the action occurred
     * @param int $userId User ID who performed the action
     * @return bool Whether logging was successful
     */
    public function logActivity($action, $description, $module, $userId = null) {
        try {
            if ($userId === null && isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }
            
            $query = "INSERT INTO activity_log (
                        user_id, action, description, module, ip_address, user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $this->db->query($query, [
                $userId,
                $action,
                $description,
                $module,
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            return true;
        } catch (Exception $e) {
            $this->error("Failed to log activity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log error message
     * @param string $message Error message
     * @param array $context Additional context
     */
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }
    
    /**
     * Log info message
     * @param string $message Info message
     * @param array $context Additional context
     */
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }
    
    /**
     * Log debug message
     * @param string $message Debug message
     * @param array $context Additional context
     */
    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }
    
    /**
     * Log warning message
     * @param string $message Warning message
     * @param array $context Additional context
     */
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Write log message to file
     * @param string $type Log type
     * @param string $message Log message
     * @param array $context Additional context
     */
    private function log($type, $message, $context = []) {
        if (!in_array($type, $this->logTypes)) {
            throw new Exception("Invalid log type: {$type}");
        }
        
        $logFile = $this->logPath . $type . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logMessage = "[{$timestamp}] {$message}{$contextStr}" . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Get activity log entries
     * @param array $filters Filter criteria
     * @param int $limit Number of entries to return
     * @param int $offset Offset for pagination
     * @return array Activity log entries
     */
    public function getActivityLog($filters = [], $limit = 50, $offset = 0) {
        try {
            $where = [];
            $params = [];
            
            if (isset($filters['user_id'])) {
                $where[] = "user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (isset($filters['action'])) {
                $where[] = "action = ?";
                $params[] = $filters['action'];
            }
            
            if (isset($filters['module'])) {
                $where[] = "module = ?";
                $params[] = $filters['module'];
            }
            
            if (isset($filters['date_from'])) {
                $where[] = "created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to'])) {
                $where[] = "created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            $whereClause = empty($where) ? "" : "WHERE " . implode(" AND ", $where);
            
            $query = "SELECT al.*, u.username 
                     FROM activity_log al 
                     LEFT JOIN users u ON al.user_id = u.id 
                     {$whereClause} 
                     ORDER BY al.created_at DESC 
                     LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            return $this->db->getRows($query, $params);
            
        } catch (Exception $e) {
            $this->error("Failed to get activity log: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean old log files
     * @param int $days Number of days to keep logs
     */
    public function cleanOldLogs($days = 30) {
        try {
            foreach ($this->logTypes as $type) {
                $logFile = $this->logPath . $type . '.log';
                
                if (file_exists($logFile)) {
                    $modTime = filemtime($logFile);
                    
                    if ($modTime < strtotime("-{$days} days")) {
                        // Archive old log
                        $archiveDir = $this->logPath . 'archive/';
                        if (!file_exists($archiveDir)) {
                            mkdir($archiveDir, 0777, true);
                        }
                        
                        $archiveFile = $archiveDir . date('Y-m-d', $modTime) . "_{$type}.log";
                        rename($logFile, $archiveFile);
                        
                        // Create new log file
                        file_put_contents($logFile, '');
                    }
                }
            }
            
            // Clean old database logs
            $query = "DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $this->db->query($query, [$days]);
            
            return true;
        } catch (Exception $e) {
            $this->error("Failed to clean old logs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get log file content
     * @param string $type Log type
     * @param int $lines Number of lines to return (0 for all)
     * @return array Log entries
     */
    public function getLogContent($type, $lines = 100) {
        if (!in_array($type, $this->logTypes)) {
            throw new Exception("Invalid log type: {$type}");
        }
        
        $logFile = $this->logPath . $type . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $content = file($logFile);
        
        if ($lines > 0) {
            $content = array_slice($content, -$lines);
        }
        
        return array_map('trim', $content);
    }
    
    /**
     * Export activity log to CSV
     * @param array $filters Filter criteria
     * @return string CSV content
     */
    public function exportActivityLog($filters = []) {
        try {
            $logs = $this->getActivityLog($filters, 0, 0); // Get all entries
            
            if (empty($logs)) {
                return '';
            }
            
            $output = fopen('php://temp', 'r+');
            
            // Add headers
            fputcsv($output, [
                'Data/Hora',
                'Usuário',
                'Ação',
                'Descrição',
                'Módulo',
                'IP',
                'User Agent'
            ]);
            
            // Add data
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['created_at'],
                    $log['username'],
                    $log['action'],
                    $log['description'],
                    $log['module'],
                    $log['ip_address'],
                    $log['user_agent']
                ]);
            }
            
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            
            return $csv;
            
        } catch (Exception $e) {
            $this->error("Failed to export activity log: " . $e->getMessage());
            return '';
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

<?php
/**
 * System Backup and Maintenance class
 */
class Backup {
    private $db;
    private $backupPath;
    private $maxBackups;
    private $tables;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->backupPath = BACKUP_PATH;
        $this->maxBackups = 10; // Keep last 10 backups
        
        // Ensure backup directory exists
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0777, true);
        }
        
        // Get all tables
        $this->tables = $this->getTables();
    }
    
    /**
     * Create database backup
     * @return string|bool Backup filename or false on failure
     */
    public function createBackup() {
        try {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupPath . $filename;
            
            $output = "-- Sistema de Vendas Database Backup\n";
            $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            // Get table structures and data
            foreach ($this->tables as $table) {
                // Get create table statement
                $stmt = $this->db->query("SHOW CREATE TABLE `{$table}`");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $output .= $row['Create Table'] . ";\n\n";
                
                // Get table data
                $stmt = $this->db->query("SELECT * FROM `{$table}`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $fields = array_map([$this, 'escapeValue'], $row);
                        $output .= "INSERT INTO `{$table}` VALUES (" . implode(", ", $fields) . ");\n";
                    }
                    $output .= "\n";
                }
            }
            
            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Save backup file
            if (file_put_contents($filepath, $output)) {
                // Compress backup
                $this->compressBackup($filepath);
                
                // Clean old backups
                $this->cleanOldBackups();
                
                return $filename;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Backup failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Restore database from backup
     * @param string $filename Backup filename
     * @return bool Whether restore was successful
     */
    public function restoreBackup($filename) {
        try {
            $filepath = $this->backupPath . $filename;
            
            // Decompress if necessary
            if (substr($filename, -3) === '.gz') {
                $filepath = $this->decompressBackup($filepath);
            }
            
            if (!file_exists($filepath)) {
                throw new Exception("Backup file not found");
            }
            
            // Read backup file
            $sql = file_get_contents($filepath);
            
            // Split into individual queries
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Execute each query
            foreach ($queries as $query) {
                if (!empty($query)) {
                    $this->db->query($query);
                }
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Clean up decompressed file if necessary
            if (substr($filename, -3) === '.gz') {
                unlink($filepath);
            }
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            
            error_log("Restore failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get list of available backups
     * @return array List of backup files with details
     */
    public function getBackups() {
        $backups = [];
        
        foreach (glob($this->backupPath . '*.sql*') as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => $this->formatSize(filesize($file)),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Sort by date descending
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $backups;
    }
    
    /**
     * Delete backup file
     * @param string $filename Backup filename
     * @return bool Whether deletion was successful
     */
    public function deleteBackup($filename) {
        $filepath = $this->backupPath . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Optimize database tables
     * @return array Results of optimization
     */
    public function optimizeTables() {
        $results = [];
        
        foreach ($this->tables as $table) {
            try {
                $this->db->query("OPTIMIZE TABLE `{$table}`");
                $results[$table] = 'success';
            } catch (Exception $e) {
                $results[$table] = 'failed: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Get database size information
     * @return array Database size details
     */
    public function getDatabaseSize() {
        $size = 0;
        $tables = [];
        
        foreach ($this->tables as $table) {
            $stmt = $this->db->query("
                SELECT 
                    data_length + index_length as size,
                    table_rows
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                AND table_name = '{$table}'
            ");
            
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            $size += $info['size'];
            $tables[$table] = [
                'size' => $this->formatSize($info['size']),
                'rows' => $info['table_rows']
            ];
        }
        
        return [
            'total_size' => $this->formatSize($size),
            'tables' => $tables
        ];
    }
    
    /**
     * Get list of database tables
     * @return array List of table names
     */
    private function getTables() {
        $stmt = $this->db->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Escape value for SQL
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    private function escapeValue($value) {
        if ($value === null) {
            return 'NULL';
        }
        return "'" . addslashes($value) . "'";
    }
    
    /**
     * Compress backup file
     * @param string $filepath Path to backup file
     */
    private function compressBackup($filepath) {
        $data = file_get_contents($filepath);
        $gzdata = gzencode($data, 9);
        file_put_contents($filepath . '.gz', $gzdata);
        unlink($filepath);
    }
    
    /**
     * Decompress backup file
     * @param string $filepath Path to compressed backup file
     * @return string Path to decompressed file
     */
    private function decompressBackup($filepath) {
        $data = file_get_contents($filepath);
        $uncompressed = gzdecode($data);
        $newPath = substr($filepath, 0, -3);
        file_put_contents($newPath, $uncompressed);
        return $newPath;
    }
    
    /**
     * Clean old backups
     */
    private function cleanOldBackups() {
        $backups = $this->getBackups();
        
        if (count($backups) > $this->maxBackups) {
            $toDelete = array_slice($backups, $this->maxBackups);
            
            foreach ($toDelete as $backup) {
                $this->deleteBackup($backup['filename']);
            }
        }
    }
    
    /**
     * Format file size
     * @param int $bytes Size in bytes
     * @return string Formatted size
     */
    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>

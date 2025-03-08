<?php
/**
 * Session management class
 */
class Session {
    private static $instance = null;
    private $isStarted = false;
    private $userId = null;
    private $userLevel = null;
    private $username = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->start();
    }
    
    /**
     * Get Session instance (Singleton)
     * @return Session
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start session with secure configuration
     */
    private function start() {
        if ($this->isStarted) {
            return;
        }
        
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSession();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            $this->regenerateSession();
        }
        
        $this->isStarted = true;
        
        // Load user data if logged in
        if (isset($_SESSION['user_id'])) {
            $this->userId = $_SESSION['user_id'];
            $this->userLevel = $_SESSION['user_level'];
            $this->username = $_SESSION['username'];
        }
    }
    
    /**
     * Regenerate session ID
     */
    private function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    /**
     * Create new user session
     * @param array $userData User data from database
     */
    public function createUserSession($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['user_level'] = $userData['user_level'];
        $_SESSION['last_activity'] = time();
        
        $this->userId = $userData['id'];
        $this->username = $userData['username'];
        $this->userLevel = $userData['user_level'];
        
        // Log login activity
        $this->logActivity('login', 'User logged in');
    }
    
    /**
     * Destroy user session
     */
    public function destroy() {
        // Log logout activity if user was logged in
        if ($this->isLoggedIn()) {
            $this->logActivity('logout', 'User logged out');
        }
        
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        // Reset instance variables
        $this->userId = null;
        $this->userLevel = null;
        $this->username = null;
        $this->isStarted = false;
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return $this->userId !== null;
    }
    
    /**
     * Check if user has required access level
     * @param string|array $requiredLevel Required access level(s)
     * @return bool
     */
    public function checkAccess($requiredLevel) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if (is_array($requiredLevel)) {
            return in_array($this->userLevel, $requiredLevel);
        }
        
        return $this->userLevel === $requiredLevel;
    }
    
    /**
     * Get current user ID
     * @return int|null
     */
    public function getUserId() {
        return $this->userId;
    }
    
    /**
     * Get current username
     * @return string|null
     */
    public function getUsername() {
        return $this->username;
    }
    
    /**
     * Get current user level
     * @return string|null
     */
    public function getUserLevel() {
        return $this->userLevel;
    }
    
    /**
     * Check session timeout
     * @return bool Whether session has timed out
     */
    public function checkTimeout() {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            $this->destroy();
            return true;
        }
        
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    /**
     * Set flash message
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message content
     */
    public function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Get flash message and clear it
     * @return array|null Flash message data
     */
    public function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
    
    /**
     * Set CSRF token
     * @return string Generated token
     */
    public function setCsrfToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
    
    /**
     * Verify CSRF token
     * @param string $token Token to verify
     * @return bool Whether token is valid
     */
    public function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        unset($_SESSION['csrf_token']);
        return $valid;
    }
    
    /**
     * Log user activity
     * @param string $action Action performed
     * @param string $description Description of the action
     */
    private function logActivity($action, $description) {
        global $conn;
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO activity_log (
                    user_id, action, description, ip_address
                ) VALUES (
                    :user_id, :action, :description, :ip_address
                )
            ");
            
            $stmt->execute([
                'user_id' => $this->userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
        } catch (PDOException $e) {
            error_log("Error logging activity: " . $e->getMessage());
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

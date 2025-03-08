<?php
/**
 * Authentication and Authorization class
 */
class Auth {
    private static $instance = null;
    private $db;
    private $session;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->session = Session::getInstance();
    }
    
    /**
     * Get Auth instance (Singleton)
     * @return Auth
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Attempt to authenticate user
     * @param string $username Username
     * @param string $password Password
     * @return bool Whether authentication was successful
     */
    public function authenticate($username, $password) {
        try {
            // Check login attempts
            if ($this->isLockedOut($username)) {
                throw new Exception('Too many failed login attempts. Please try again later.');
            }
            
            // Get user from database
            $query = "SELECT id, username, password_hash, full_name, user_level, status 
                     FROM users 
                     WHERE username = ?";
            
            $user = $this->db->getRow($query, [$username]);
            
            if (!$user) {
                $this->recordLoginAttempt($username, false);
                throw new Exception('Invalid username or password');
            }
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                throw new Exception('This account is inactive');
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordLoginAttempt($username, false);
                throw new Exception('Invalid username or password');
            }
            
            // Check if password needs rehash
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $this->updatePassword($user['id'], $password);
            }
            
            // Clear login attempts
            $this->clearLoginAttempts($username);
            
            // Create session
            $this->session->createUserSession($user);
            
            // Record successful login
            $this->recordLoginAttempt($username, true);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Authentication failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if user is locked out due to too many failed attempts
     * @param string $username Username
     * @return bool
     */
    private function isLockedOut($username) {
        $query = "SELECT COUNT(*) as attempts 
                 FROM login_attempts 
                 WHERE username = ? 
                 AND success = 0 
                 AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        
        $result = $this->db->getRow($query, [$username]);
        return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Record login attempt
     * @param string $username Username
     * @param bool $success Whether attempt was successful
     */
    private function recordLoginAttempt($username, $success) {
        $query = "INSERT INTO login_attempts (username, ip_address, success) 
                 VALUES (?, ?, ?)";
        
        $this->db->query($query, [
            $username,
            $_SERVER['REMOTE_ADDR'],
            $success ? 1 : 0
        ]);
    }
    
    /**
     * Clear login attempts for user
     * @param string $username Username
     */
    private function clearLoginAttempts($username) {
        $query = "DELETE FROM login_attempts WHERE username = ?";
        $this->db->query($query, [$username]);
    }
    
    /**
     * Update user's password
     * @param int $userId User ID
     * @param string $password New password
     */
    private function updatePassword($userId, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "UPDATE users SET password_hash = ? WHERE id = ?";
        $this->db->query($query, [$hash, $userId]);
    }
    
    /**
     * Check if user has required permission
     * @param string $permission Permission to check
     * @return bool
     */
    public function hasPermission($permission) {
        if (!$this->session->isLoggedIn()) {
            return false;
        }
        
        $userLevel = $this->session->getUserLevel();
        
        // Define permission hierarchy
        $permissions = [
            'admin' => [
                'manage_users',
                'manage_settings',
                'view_reports',
                'manage_sales',
                'manage_products',
                'manage_customers',
                'view_dashboard'
            ],
            'super' => [
                'view_reports',
                'manage_sales',
                'manage_products',
                'manage_customers',
                'view_dashboard'
            ],
            'user' => [
                'manage_sales',
                'view_dashboard'
            ]
        ];
        
        return in_array($permission, $permissions[$userLevel] ?? []);
    }
    
    /**
     * Check if user can access specific module
     * @param string $module Module name
     * @return bool
     */
    public function canAccessModule($module) {
        if (!$this->session->isLoggedIn()) {
            return false;
        }
        
        $userLevel = $this->session->getUserLevel();
        
        // Define module access by user level
        $moduleAccess = [
            'admin' => [
                'dashboard',
                'sales',
                'products',
                'customers',
                'reports',
                'users',
                'settings',
                'financial'
            ],
            'super' => [
                'dashboard',
                'sales',
                'products',
                'customers',
                'reports',
                'financial'
            ],
            'user' => [
                'dashboard',
                'sales'
            ]
        ];
        
        return in_array($module, $moduleAccess[$userLevel] ?? []);
    }
    
    /**
     * Log out current user
     */
    public function logout() {
        $this->session->destroy();
    }
    
    /**
     * Change user's password
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @throws Exception if current password is invalid
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get user's current password hash
        $query = "SELECT password_hash FROM users WHERE id = ?";
        $user = $this->db->getRow($query, [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            throw new Exception('Current password is invalid');
        }
        
        // Update password
        $this->updatePassword($userId, $newPassword);
    }
    
    /**
     * Reset user's password and send email
     * @param string $username Username
     * @return bool Whether reset was successful
     */
    public function resetPassword($username) {
        try {
            // Get user
            $query = "SELECT id, email FROM users WHERE username = ? AND status = 'active'";
            $user = $this->db->getRow($query, [$username]);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Generate new password
            $newPassword = bin2hex(random_bytes(8));
            $this->updatePassword($user['id'], $newPassword);
            
            // Send email with new password
            $subject = 'Password Reset - ' . SYSTEM_NAME;
            $message = "Your password has been reset.\n\n";
            $message .= "New password: " . $newPassword . "\n\n";
            $message .= "Please change your password after logging in.";
            
            return mail($user['email'], $subject, $message);
            
        } catch (Exception $e) {
            error_log("Password reset failed: " . $e->getMessage());
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

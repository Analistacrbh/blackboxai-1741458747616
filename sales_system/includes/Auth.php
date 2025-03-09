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
            // Get user from database
            $stmt = $this->db->getConnection()->prepare("
                SELECT id, username, password_hash, full_name, user_level, status 
                FROM users 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception('Usuário ou senha inválidos');
            }
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                throw new Exception('Esta conta está inativa');
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                throw new Exception('Usuário ou senha inválidos');
            }
            
            // Create session
            $this->session->set('user_id', $user['id']);
            $this->session->set('username', $user['username']);
            $this->session->set('full_name', $user['full_name']);
            $this->session->set('user_level', $user['user_level']);
            
            // Update last login
            $stmt = $this->db->getConnection()->prepare("
                UPDATE users 
                SET last_login = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Authentication failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if user has required permission
     * @param string|array $permission Permission to check
     * @return bool
     */
    public function hasPermission($permission) {
        if (!$this->session->isLoggedIn()) {
            return false;
        }
        
        $userLevel = $this->session->get('user_level');
        
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
        
        if (is_array($permission)) {
            return count(array_intersect($permission, $permissions[$userLevel] ?? [])) > 0;
        }
        
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
        
        $userLevel = $this->session->get('user_level');
        
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
     * Prevent cloning of the instance (Singleton)
     */
    private function __clone() {}
    
    /**
     * Prevent unserialize of the instance (Singleton)
     */
    private function __wakeup() {}
}
?>
